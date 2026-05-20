<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD over an application's environment variables.
 *
 * Variables are addressed by name; `set()` is idempotent (create or update),
 * `remove()` deletes the entry. Updates are reflected on the next deploy.
 */
final class EnvironmentController extends AbstractController
{
    public function __construct(private readonly Client $cc)
    {
    }

    #[Route('/applications/{id}/env', name: 'application_env', methods: ['GET'])]
    public function index(Request $request, string $id): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $application = $this->cc->applications->get($id, $owner);
            $env = $this->cc->environment->list($id, $owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        ksort($env);

        return $this->render('application/env.html.twig', [
            'application' => $application,
            'env' => $env,
            'owner' => $owner,
        ]);
    }

    #[Route('/applications/{id}/env', name: 'application_env_set', methods: ['POST'])]
    public function set(Request $request, string $id): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));
        $name = $request->request->get('name');
        $value = $request->request->get('value');

        if (!\is_string($name) || '' === trim($name)) {
            $this->addFlash('error', 'Nom de variable requis.');

            return $this->envRedirect($id, $owner);
        }
        if (!\is_string($value)) {
            $value = '';
        }

        try {
            $this->cc->environment->set($id, trim($name), $value, $owner);
            $this->addFlash('success', \sprintf('Variable %s enregistrée. Redéploie pour appliquer.', trim($name)));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de l\'écriture : %s', $e->getMessage()));
        }

        return $this->envRedirect($id, $owner);
    }

    #[Route('/applications/{id}/env/{name}', name: 'application_env_remove', methods: ['POST'])]
    public function remove(Request $request, string $id, string $name): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));

        try {
            $this->cc->environment->remove($id, $name, $owner);
            $this->addFlash('success', \sprintf('Variable %s supprimée.', $name));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de la suppression : %s', $e->getMessage()));
        }

        return $this->envRedirect($id, $owner);
    }

    private function envRedirect(string $id, ?string $owner): RedirectResponse
    {
        return $this->redirectToRoute(
            'application_env',
            null === $owner ? ['id' => $id] : ['id' => $id, 'owner' => $owner],
        );
    }

    private function normaliseOwner(mixed $raw): ?string
    {
        if (!\is_string($raw) || '' === $raw || 'self' === $raw) {
            return null;
        }
        if (str_starts_with($raw, 'user_')) {
            return null;
        }

        return $raw;
    }
}
