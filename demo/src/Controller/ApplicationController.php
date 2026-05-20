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

        $query = null === $owner ? [] : ['owner' => $owner];

        return $this->redirectToRoute('application_list', $query);
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

        $query = null === $owner ? [] : ['owner' => $owner];

        return $this->redirectToRoute('application_list', $query);
    }

    private function normaliseOwner(mixed $raw): ?string
    {
        if (!\is_string($raw) || '' === $raw || 'self' === $raw) {
            return null;
        }

        return $raw;
    }
}
