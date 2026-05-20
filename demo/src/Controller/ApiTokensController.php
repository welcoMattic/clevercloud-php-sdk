<?php

namespace App\Controller;

use App\Service\ClevercloudClientFactory;
use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manage personal API tokens via the `api-bridge.clever-cloud.com` gateway.
 *
 * Only works when the session is authenticated with a Bearer token — the
 * gateway returns 401 for OAuth1 callers. The list page surfaces a notice
 * when the user is OAuth1-authed.
 */
final class ApiTokensController extends AbstractController
{
    public function __construct(
        private readonly Client $cc,
        private readonly ClevercloudClientFactory $factory,
    ) {
    }

    #[Route('/api-tokens', name: 'api_tokens_list', methods: ['GET'])]
    public function list(): Response
    {
        if ('api-token' !== $this->factory->authMode()) {
            return $this->render('api_tokens/oauth_blocked.html.twig');
        }

        try {
            $tokens = $this->cc->apiTokens->list();
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('api_tokens/list.html.twig', [
            'tokens' => $tokens,
            'lastMinted' => null,
        ]);
    }

    #[Route('/api-tokens', name: 'api_tokens_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if ('api-token' !== $this->factory->authMode()) {
            return $this->render('api_tokens/oauth_blocked.html.twig');
        }

        $name = $request->request->get('name');
        if (!\is_string($name) || '' === trim($name)) {
            $this->addFlash('error', 'Nom du token requis.');

            return $this->redirectToRoute('api_tokens_list');
        }

        $scopesRaw = $request->request->get('scopes');
        $scopes = [];
        if (\is_string($scopesRaw) && '' !== trim($scopesRaw)) {
            $scopes = array_values(array_filter(array_map('trim', explode(',', $scopesRaw))));
        }

        $payload = ['name' => trim($name)];
        if ([] !== $scopes) {
            $payload['scopes'] = $scopes;
        }

        try {
            /** @phpstan-var array{name: string, scopes?: list<string>, expires_at?: string|null} $payload */
            $token = $this->cc->apiTokens->create($payload);
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de la création : %s', $e->getMessage()));

            return $this->redirectToRoute('api_tokens_list');
        }

        try {
            $tokens = $this->cc->apiTokens->list();
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        $this->addFlash('success', \sprintf('Token "%s" créé — copie-le maintenant, il ne sera plus jamais affiché.', $token->name));

        return $this->render('api_tokens/list.html.twig', [
            'tokens' => $tokens,
            'lastMinted' => $token,
        ]);
    }

    #[Route('/api-tokens/{id}', name: 'api_tokens_delete', methods: ['POST'])]
    public function delete(string $id): RedirectResponse
    {
        if ('api-token' !== $this->factory->authMode()) {
            return $this->redirectToRoute('api_tokens_list');
        }

        try {
            $this->cc->apiTokens->delete($id);
            $this->addFlash('success', \sprintf('Token %s révoqué.', $id));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de la révocation : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('api_tokens_list');
    }
}
