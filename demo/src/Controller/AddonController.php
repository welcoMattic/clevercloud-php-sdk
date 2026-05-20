<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddonController extends AbstractController
{
    public function __construct(private readonly Client $cc)
    {
    }

    #[Route('/addons', name: 'addon_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $organisations = $this->cc->organisations->list();
            $addons = $this->cc->addons->list($owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('addon/list.html.twig', [
            'addons' => $addons,
            'organisations' => $organisations,
            'selectedOwner' => $owner,
        ]);
    }

    private function normaliseOwner(mixed $raw): ?string
    {
        if (!\is_string($raw) || '' === $raw || 'self' === $raw) {
            return null;
        }

        return $raw;
    }
}
