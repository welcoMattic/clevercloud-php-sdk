<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganisationController extends AbstractController
{
    public function __construct(private readonly Client $cc)
    {
    }

    #[Route('/organisations', name: 'organisation_list')]
    public function list(): Response
    {
        try {
            $organisations = $this->cc->organisations->list();
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('organisation/list.html.twig', ['organisations' => $organisations]);
    }
}
