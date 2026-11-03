<?php

namespace CleverCloud\Sdk\Tests\OpenApi;

/**
 * SDK calls the drift check accepts although the spec does not document them.
 *
 * `pattern` is an fnmatch() glob over the normalised SDK path (parameters as
 * `{}`, non-default host prefixed), `method` is an HTTP method or `*`.
 * An entry that no longer matches anything, or only matches calls the spec
 * now documents, is reported as stale by `composer openapi:drift`: prune it.
 */
final class Allowlist
{
    /**
     * @return list<array{method: string, pattern: string, reason: string}>
     */
    public static function entries(): array
    {
        return [
            // ------------------------------------------------------------------
            //  Families the unified spec does not document yet. They exist and
            //  work against the live API; absence from the spec proves nothing.
            // ------------------------------------------------------------------

            // Product catalog (instances, add-on providers and plans, zones, countries).
            ['method' => '*', 'pattern' => '/v2/products*', 'reason' => 'v2 products catalog not in the spec'],
            // Other users' public profile, apps and add-ons.
            ['method' => '*', 'pattern' => '/v2/users/*', 'reason' => 'v2 users not in the spec'],
            // TCP redirections, per application and their namespaces, both owners.
            ['method' => '*', 'pattern' => '/v2/*/tcp-redirs*', 'reason' => 'v2 tcp-redirs not in the spec'],
            // Invoices, payment methods, consumptions, credits.
            ['method' => '*', 'pattern' => '/v4/billing/*', 'reason' => 'v4 billing not in the spec'],
            // Log drains.
            ['method' => '*', 'pattern' => '/v4/drains/*', 'reason' => 'v4 drains not in the spec'],
            // E-mail hooks and webhooks.
            ['method' => '*', 'pattern' => '/v4/notifications/*', 'reason' => 'v4 notifications not in the spec'],
            // Application load balancers and their DNS info.
            ['method' => '*', 'pattern' => '/v4/load-balancers/*', 'reason' => 'v4 load-balancers not in the spec'],
            // Orchestration view of instances and deployments.
            ['method' => '*', 'pattern' => '/v4/orchestration/*', 'reason' => 'v4 orchestration not in the spec'],
            // Add-on backups (any provider).
            ['method' => '*', 'pattern' => '/v4/addon-providers/*/addons/*/backups*', 'reason' => 'v4 add-on backups not in the spec'],
            // Network groups owned by the current user: the spec only documents the organisation owner.
            ['method' => '*', 'pattern' => '/v4/networkgroups/self/*', 'reason' => 'networkgroups /self owner variant not in the spec'],
            // API tokens live on the api-bridge host, outside the spec.
            ['method' => '*', 'pattern' => 'api-bridge.clever-cloud.com/api-tokens*', 'reason' => 'api-bridge api-tokens not in the spec'],
        ];
    }
}
