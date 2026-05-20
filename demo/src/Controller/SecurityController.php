<?php

namespace App\Controller;

use App\Service\ClevercloudClientFactory;
use CleverCloud\Sdk\Auth\OAuthFlow;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Two ways to log into the demo:
 *
 *   /login                → chooser page (OAuth1 or API token)
 *   /login/oauth          → POST request_token, redirect the user to authorize URL
 *   /oauth/callback       → receive oauth_token + oauth_verifier, POST access_token
 *   /login/token  (GET)   → paste-an-API-token form
 *   /login/token  (POST)  → store the token in session, redirect to /
 *   /logout       (POST)  → clear the session
 */
final class SecurityController extends AbstractController
{
    private const string SESSION_REQUEST_TOKEN = 'cc_request_token';
    private const string SESSION_REQUEST_TOKEN_SECRET = 'cc_request_token_secret';

    public function __construct(
        private readonly ClevercloudClientFactory $factory,
        private readonly OAuthFlow $oauth,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(): Response
    {
        return $this->render('security/login.html.twig', [
            'hasConsumer' => '' !== $this->factory->consumerKey() && '' !== $this->factory->consumerSecret(),
        ]);
    }

    #[Route('/login/oauth', name: 'login_oauth', methods: ['POST'])]
    public function loginOAuth(Request $request, UrlGeneratorInterface $urls): Response
    {
        $session = $request->getSession();

        $consumerKey = $this->factory->consumerKey();
        $consumerSecret = $this->factory->consumerSecret();

        if ('' === $consumerKey || '' === $consumerSecret) {
            return $this->render('security/missing_consumer.html.twig');
        }

        $callback = $urls->generate('oauth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $request_token = $this->oauth->requestToken($consumerKey, $consumerSecret, $callback);
        } catch (CleverCloudException $e) {
            return $this->render('security/error.html.twig', ['exception' => $e]);
        }

        $session->set(self::SESSION_REQUEST_TOKEN, $request_token['token']);
        $session->set(self::SESSION_REQUEST_TOKEN_SECRET, $request_token['tokenSecret']);

        return new RedirectResponse($this->oauth->authorizationUrl($request_token['token']));
    }

    #[Route('/oauth/callback', name: 'oauth_callback', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $session = $request->getSession();

        $returnedToken = $request->query->get('oauth_token');
        $verifier = $request->query->get('oauth_verifier');

        if (!\is_string($returnedToken) || !\is_string($verifier)) {
            $this->addFlash('error', 'Callback Clever Cloud sans oauth_token / oauth_verifier.');

            return $this->redirectToRoute('login');
        }

        $stashedToken = $session->get(self::SESSION_REQUEST_TOKEN);
        $stashedSecret = $session->get(self::SESSION_REQUEST_TOKEN_SECRET);

        if (!\is_string($stashedToken) || !\is_string($stashedSecret) || $stashedToken !== $returnedToken) {
            $this->addFlash('error', 'Le request_token renvoyé ne correspond pas à la session — recommence le login.');

            return $this->redirectToRoute('login');
        }

        try {
            $access = $this->oauth->accessToken(
                $this->factory->consumerKey(),
                $this->factory->consumerSecret(),
                $stashedToken,
                $stashedSecret,
                $verifier,
            );
        } catch (CleverCloudException $e) {
            return $this->render('security/error.html.twig', ['exception' => $e]);
        }

        $session->remove(self::SESSION_REQUEST_TOKEN);
        $session->remove(self::SESSION_REQUEST_TOKEN_SECRET);
        $session->set(ClevercloudClientFactory::SESSION_TOKEN, $access['token']);
        $session->set(ClevercloudClientFactory::SESSION_TOKEN_SECRET, $access['tokenSecret']);

        $this->addFlash('success', 'Connecté à Clever Cloud via OAuth 1.0a.');

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/login/token', name: 'login_token', methods: ['GET', 'POST'])]
    public function loginToken(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('api_token');
            if (!\is_string($token) || '' === trim($token)) {
                $this->addFlash('error', 'Token vide — colle un token Clever Cloud valide.');

                return $this->redirectToRoute('login_token');
            }

            $request->getSession()->set(ClevercloudClientFactory::SESSION_API_TOKEN, trim($token));
            $this->addFlash('success', 'Connecté à Clever Cloud via API token.');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('security/login_token.html.twig');
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): RedirectResponse
    {
        $request->getSession()->clear();
        $this->addFlash('success', 'Déconnecté.');

        return $this->redirectToRoute('login');
    }
}
