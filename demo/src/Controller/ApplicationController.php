<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            $vhosts = $this->cc->domains->list($id, $owner);
            $branches = $this->cc->applications->branches($id, $owner);
            $tcpRedirs = $this->cc->tcpRedirections->list($id, $owner);
            $namespaces = $this->cc->tcpRedirections->namespaces($owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('application/show.html.twig', [
            'app' => $application,
            'vhosts' => $vhosts,
            'branches' => $branches,
            'tcpRedirs' => $tcpRedirs,
            'namespaces' => $namespaces,
            'owner' => $owner,
        ]);
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
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec du déploiement : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('application_show', $this->ownerQuery($id, $owner));
    }

    #[Route('/applications/{id}/restart', name: 'application_restart', methods: ['POST'])]
    public function restart(Request $request, string $id): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));
        $withoutCache = (bool) $request->request->get('withoutCache', false);

        try {
            $this->cc->applications->restart($id, $owner, withoutCache: $withoutCache);
            $this->addFlash('success', \sprintf('Redéploiement de %s lancé.', $id));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec du redéploiement : %s', $e->getMessage()));
        }

        return $this->redirectToRoute('application_list', null === $owner ? [] : ['owner' => $owner]);
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

    private function normaliseOwner(mixed $raw): ?string
    {
        if (!\is_string($raw) || '' === $raw || 'self' === $raw) {
            return null;
        }

        return $raw;
    }
}
