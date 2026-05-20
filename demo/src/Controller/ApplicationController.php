<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApplicationController extends AbstractController
{
    public function __construct(private readonly Client $cc)
    {
    }

    #[Route('/applications', name: 'application_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $organisations = $this->cc->organisations->list();
            $applications = $this->cc->applications->list($owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('application/list.html.twig', [
            'applications' => $applications,
            'organisations' => $organisations,
            'selectedOwner' => $owner,
        ]);
    }

    #[Route('/applications/{id}', name: 'application_show', methods: ['GET'])]
    public function show(Request $request, string $id): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $application = $this->cc->applications->get($id, $owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        // Sub-resources may fail independently (provider not supporting an endpoint,
        // permissions, etc.) — keep the page rendering and surface the error per section.
        $vhosts = null;
        $vhostsError = null;
        try {
            $vhosts = $this->cc->domains->list($id, $owner);
        } catch (CleverCloudException $e) {
            $vhostsError = $e->getMessage();
        }

        $branches = null;
        $branchesError = null;
        try {
            $branches = $this->cc->applications->branches($id, $owner);
        } catch (CleverCloudException $e) {
            $branchesError = $e->getMessage();
        }

        $tcpRedirs = null;
        $namespaces = [];
        $tcpError = null;
        try {
            $tcpRedirs = $this->cc->tcpRedirections->list($id, $owner);
            $namespaces = $this->cc->tcpRedirections->namespaces($owner);
        } catch (CleverCloudException $e) {
            $tcpError = $e->getMessage();
        }

        return $this->render('application/show.html.twig', [
            'application' => $application,
            'vhosts' => $vhosts,
            'vhostsError' => $vhostsError,
            'branches' => $branches,
            'branchesError' => $branchesError,
            'tcpRedirs' => $tcpRedirs,
            'namespaces' => $namespaces,
            'tcpError' => $tcpError,
            'owner' => $owner,
        ]);
    }

    #[Route('/applications/{id}/state.json', name: 'application_state', methods: ['GET'])]
    public function state(Request $request, string $id): JsonResponse
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $app = $this->cc->applications->get($id, $owner);
        } catch (CleverCloudException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 502);
        }

        return new JsonResponse(['state' => $app->state]);
    }

    #[Route('/applications/{id}/deploy', name: 'application_deploy', methods: ['POST'])]
    public function deploy(Request $request, string $id): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));
        $commit = $request->request->get('commit');
        $commit = \is_string($commit) && '' !== trim($commit) ? trim($commit) : null;

        try {
            $this->cc->applications->deploy($id, $commit, $owner);
            $message = null === $commit
                ? \sprintf('Déploiement de %s déclenché.', $id)
                : \sprintf('Déploiement de %s sur %s déclenché.', $id, $commit);
            $this->addFlash('success', $message);

            return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner) + ['deploying' => 1]);
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec du déploiement : %s', $e->getMessage()));

            return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner));
        }
    }

    #[Route('/applications/{id}/restart', name: 'application_restart', methods: ['POST'])]
    public function restart(Request $request, string $id): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));
        $withoutCache = (bool) $request->request->get('withoutCache', false);

        try {
            $this->cc->applications->restart($id, $owner, withoutCache: $withoutCache);
            $this->addFlash('success', \sprintf('Redéploiement de %s lancé.', $id));

            return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner) + ['deploying' => 1]);
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec du redéploiement : %s', $e->getMessage()));

            return $this->redirectToRoute('application_list', null === $owner ? [] : ['owner' => $owner]);
        }
    }

    #[Route('/applications/{id}/stop', name: 'application_stop', methods: ['POST'])]
    public function stop(Request $request, string $id): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));

        try {
            $this->cc->applications->stop($id, $owner);
            $this->addFlash('success', \sprintf('Arrêt de %s demandé.', $id));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de l\'arrêt : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('application_list', null === $owner ? [] : ['owner' => $owner]);
    }

    #[Route('/applications/{id}/tcp-redirs', name: 'application_tcp_add', methods: ['POST'])]
    public function addTcpRedirection(Request $request, string $id): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));
        $namespace = $request->request->get('namespace');

        if (!\is_string($namespace) || '' === trim($namespace)) {
            $this->addFlash('error', 'Namespace manquant.');

            return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner));
        }

        try {
            $redir = $this->cc->tcpRedirections->add($id, trim($namespace), $owner);
            $this->addFlash('success', \sprintf('Port TCP %d ouvert sur le namespace %s.', $redir->port, $redir->namespace));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de l\'ouverture TCP : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner));
    }

    #[Route('/applications/{id}/tcp-redirs/{port}', name: 'application_tcp_remove', methods: ['POST'])]
    public function removeTcpRedirection(Request $request, string $id, int $port): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));
        $namespace = $request->request->get('namespace');

        if (!\is_string($namespace) || '' === trim($namespace)) {
            $this->addFlash('error', 'Namespace manquant.');

            return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner));
        }

        try {
            $this->cc->tcpRedirections->remove($id, $port, trim($namespace), $owner);
            $this->addFlash('success', \sprintf('Port TCP %d fermé.', $port));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de la fermeture TCP : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner));
    }

    /**
     * @return array{id: string, owner?: string}
     */
    private function ownerQuery(string $id, ?string $owner): array
    {
        return null === $owner ? ['id' => $id] : ['id' => $id, 'owner' => $owner];
    }

    /**
     * Returns null when the owner should resolve to `/self` — i.e. empty,
     * literal `self`, or a `user_xxx` identifier (Clever Cloud user IDs are
     * not routable under `/organisations/{id}` and must be addressed as self).
     */
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
