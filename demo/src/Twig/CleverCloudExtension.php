<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig helpers that translate Clever Cloud API codes (app states, deployment
 * states, migration statuses…) to human-readable French labels with a CSS
 * variant class so templates don't have to repeat the mapping.
 */
final class CleverCloudExtension extends AbstractExtension
{
    /** @return list<TwigFilter> */
    public function getFilters(): array
    {
        return [
            new TwigFilter('cc_state_label', $this->stateLabel(...)),
            new TwigFilter('cc_state_variant', $this->stateVariant(...)),
            new TwigFilter('cc_addon_status_label', $this->addonStatusLabel(...)),
            new TwigFilter('cc_migration_status_label', $this->migrationStatusLabel(...)),
            new TwigFilter('cc_migration_status_variant', $this->migrationStatusVariant(...)),
            new TwigFilter('cc_deployment_state_label', $this->deploymentStateLabel(...)),
        ];
    }

    public function stateLabel(?string $state): string
    {
        return match ($state) {
            'SHOULD_BE_UP' => 'En ligne',
            'WANTS_TO_BE_UP' => 'Démarrage',
            'SHOULD_BE_DOWN' => 'Arrêtée',
            'WANTS_TO_BE_DOWN' => 'Arrêt en cours',
            'RESTART' => 'Redémarrage',
            'RESTART_REQUESTED' => 'Redémarrage demandé',
            'RESTART_FAILED' => 'Redémarrage échoué',
            'DEPLOYING' => 'Déploiement',
            'DEPLOYMENT_PENDING' => 'Déploiement en attente',
            null, '' => '—',
            default => ucfirst(strtolower(str_replace('_', ' ', $state))),
        };
    }

    public function stateVariant(?string $state): string
    {
        return match ($state) {
            'SHOULD_BE_UP' => 'ok',
            'WANTS_TO_BE_UP', 'RESTART', 'RESTART_REQUESTED', 'DEPLOYING', 'DEPLOYMENT_PENDING' => 'warn',
            'SHOULD_BE_DOWN' => 'neutral',
            'WANTS_TO_BE_DOWN' => 'warn',
            'RESTART_FAILED' => 'fail',
            null, '' => 'neutral',
            default => 'neutral',
        };
    }

    public function addonStatusLabel(?string $status): string
    {
        return match ($status) {
            'TO_DEPLOY' => 'À déployer',
            'PROVIDED' => 'Provisionné',
            'PROVIDING' => 'En cours de provisionnement',
            'PROVIDING_FAILED' => 'Échec du provisionnement',
            'PAUSED' => 'En pause',
            null, '' => '—',
            default => ucfirst(strtolower(str_replace('_', ' ', $status))),
        };
    }

    public function migrationStatusLabel(?string $status): string
    {
        return match ($status) {
            'success' => 'Succès',
            'in-progress' => 'En cours',
            'pending' => 'En attente',
            'failed' => 'Échouée',
            'cancelled' => 'Annulée',
            null, '' => '—',
            default => ucfirst($status),
        };
    }

    public function migrationStatusVariant(?string $status): string
    {
        return match ($status) {
            'success' => 'ok',
            'in-progress', 'pending' => 'warn',
            'failed' => 'fail',
            'cancelled' => 'neutral',
            default => 'neutral',
        };
    }

    public function deploymentStateLabel(?string $state): string
    {
        return match ($state) {
            'OK' => 'Réussi',
            'FAIL' => 'Échec',
            'CANCELLED' => 'Annulé',
            'WIP' => 'En cours',
            'QUEUED' => 'En file',
            null, '' => '—',
            default => ucfirst(strtolower($state)),
        };
    }
}
