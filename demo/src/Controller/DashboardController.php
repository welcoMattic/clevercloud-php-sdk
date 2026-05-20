<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(private readonly Client $cc)
    {
    }

    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        try {
            $me = $this->cc->self->get();
            $organisations = $this->cc->organisations->list();
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('dashboard/index.html.twig', [
            'me' => $me,
            'organisations' => $organisations,
        ]);
    }
}
