<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    #[Route('/addons/new', name: 'addon_new', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner') ?? $request->request->get('owner'));

        if ($request->isMethod('POST')) {
            $payload = [];
            foreach (['name', 'providerId', 'plan', 'region'] as $field) {
                $value = $request->request->get($field);
                if (\is_string($value) && '' !== trim($value)) {
                    $payload[$field] = trim($value);
                }
            }

            if (!isset($payload['name'], $payload['providerId'], $payload['plan'])) {
                $this->addFlash('error', 'Nom, provider et plan requis.');

                return $this->redirectToRoute('addon_new', null === $owner ? [] : ['owner' => $owner]);
            }

            try {
                $created = $this->cc->addons->create($payload, $owner);
                $this->addFlash('success', \sprintf('Add-on %s créé.', $created->name));

                return $this->redirectToRoute(
                    'addon_show',
                    null === $owner ? ['id' => $created->id] : ['id' => $created->id, 'owner' => $owner],
                );
            } catch (CleverCloudException $e) {
                $this->addFlash('error', \sprintf('Échec de la création : %s', $e->getMessage()));
            }
        }

        try {
            $organisations = $this->cc->organisations->list();
            $providers = $this->cc->addons->providers();
            $zones = $this->cc->products->zones();
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('addon/new.html.twig', [
            'organisations' => $organisations,
            'providers' => $providers,
            'zones' => $zones,
            'owner' => $owner,
        ]);
    }

    #[Route('/addons/{id}', name: 'addon_show', methods: ['GET'], requirements: ['id' => 'addon_[^/]+'])]
    public function show(Request $request, string $id): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $addon = $this->cc->addons->get($id, $owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        // Sub-resources may not exist for every add-on type — let each fail
        // independently and render the available sections.
        $migrations = null;
        $migrationsError = null;
        try {
            $migrations = $this->cc->addons->listMigrations($id, $owner);
        } catch (CleverCloudException $e) {
            $migrationsError = $e->getMessage();
        }

        $backups = [];
        $backupsError = null;
        $providerId = $addon->provider?->id;
        if (null !== $providerId && null !== $addon->realId) {
            try {
                $backups = $this->cc->backups->list($providerId, $addon->realId);
            } catch (CleverCloudException $e) {
                $backupsError = $e->getMessage();
            }
        } else {
            $backupsError = 'Cet add-on ne fournit pas de provider/realId exploitable.';
        }

        return $this->render('addon/show.html.twig', [
            'addon' => $addon,
            'migrations' => $migrations,
            'migrationsError' => $migrationsError,
            'backups' => $backups,
            'backupsError' => $backupsError,
            'owner' => $owner,
        ]);
    }

    #[Route('/addons/{id}/backups/{backupId}/restore', name: 'addon_backup_restore', methods: ['POST'])]
    public function restoreBackup(Request $request, string $id, string $backupId): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));

        try {
            $addon = $this->cc->addons->get($id, $owner);
            $providerId = $addon->provider?->id;
            $realId = $addon->realId;

            if (null === $providerId || null === $realId) {
                $this->addFlash('error', "Add-on sans provider/realId — impossible de restaurer.");
            } else {
                $this->cc->backups->restore($providerId, $realId, $backupId);
                $this->addFlash('success', \sprintf('Restauration du backup %s lancée.', $backupId));
            }
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de la restauration : %s', $e->getMessage()));
        }

        return $this->redirectToRoute(
            'addon_show',
            null === $owner ? ['id' => $id] : ['id' => $id, 'owner' => $owner],
        );
    }

    #[Route('/addons/{id}/migrations/{migrationId}/cancel', name: 'addon_migration_cancel', methods: ['POST'])]
    public function cancelMigration(Request $request, string $id, string $migrationId): RedirectResponse
    {
        $owner = $this->normaliseOwner($request->request->get('owner'));

        try {
            $this->cc->addons->cancelMigration($id, $migrationId, $owner);
            $this->addFlash('success', \sprintf('Migration %s annulée.', $migrationId));
        } catch (CleverCloudException $e) {
            $this->addFlash('error', \sprintf('Échec de l\'annulation : %s', $e->getMessage()));
        }

        return $this->redirectToRoute(
            'addon_show',
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
