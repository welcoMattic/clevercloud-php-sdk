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
            // Operator collection routes (list, create) and item update / delete: the spec only has get and actions.
            ['method' => 'GET', 'pattern' => '/v4/addon-providers/addon-*/addons', 'reason' => 'operator list not in the spec'],
            ['method' => 'POST', 'pattern' => '/v4/addon-providers/addon-*/addons', 'reason' => 'operator create not in the spec'],
            ['method' => 'PUT', 'pattern' => '/v4/addon-providers/addon-*/addons/{}', 'reason' => 'operator update not in the spec'],
            ['method' => 'DELETE', 'pattern' => '/v4/addon-providers/addon-*/addons/{}', 'reason' => 'operator delete not in the spec'],
            // Network groups owned by the current user: the spec only documents the organisation owner.
            ['method' => '*', 'pattern' => '/v4/networkgroups/self/*', 'reason' => 'networkgroups /self owner variant not in the spec'],
            // API tokens live on the api-bridge host, outside the spec.
            ['method' => '*', 'pattern' => 'api-bridge.clever-cloud.com/api-tokens*', 'reason' => 'api-bridge api-tokens not in the spec'],

            // ------------------------------------------------------------------
            //  Known drift: the SDK route differs from the documented one.
            //  Each entry is a bug to fix in the SDK (or to confirm against the
            //  live API when the spec is wrong). Remove the entry with the fix.
            // ------------------------------------------------------------------

            // Spec: DELETE .../deployments/{deploymentId}/instances.
            ['method' => 'DELETE', 'pattern' => '/v2/*/applications/{}/deployments/{}', 'reason' => 'drift: cancel deployment lacks /instances'],
            // Spec: PUT .../vhosts/{domain}, and no GET on a single vhost.
            ['method' => 'POST', 'pattern' => '/v2/*/applications/{}/vhosts/{}', 'reason' => 'drift: add vhost is PUT in the spec'],
            ['method' => 'GET', 'pattern' => '/v2/*/applications/{}/vhosts/{}', 'reason' => 'drift: no single vhost GET in the spec'],
            // Spec: add-on migrations only under /organisations/{id}, preorder is GET .../migrations/preorders.
            ['method' => '*', 'pattern' => '/v2/self/addons/{}/migrations*', 'reason' => 'drift: self add-on migrations not in the spec'],
            ['method' => 'POST', 'pattern' => '/v2/organisations/{}/addons/{}/migrations/preorder', 'reason' => 'drift: preorder is GET .../migrations/preorders'],
            // Spec: GET and PATCH only, no DELETE to reset the policy.
            ['method' => 'DELETE', 'pattern' => '/v4/addon-providers/addon-pulsar/addons/{}/storage-policies', 'reason' => 'drift: no storage-policies DELETE in the spec'],
            // Spec: network group link only for Keycloak and Otoroshi.
            ['method' => '*', 'pattern' => '/v4/addon-providers/addon-matomo/addons/{}/networkgroup', 'reason' => 'drift: no Matomo network group link in the spec'],
            ['method' => '*', 'pattern' => '/v4/addon-providers/addon-metabase/addons/{}/networkgroup', 'reason' => 'drift: no Metabase network group link in the spec'],
            // Spec: GET .../external-peers/{peerId}, no /config sub-resource.
            ['method' => 'GET', 'pattern' => '/v4/networkgroups/organisations/{}/networkgroups/{}/external-peers/{}/config', 'reason' => 'drift: no external peer /config in the spec'],
        ];
    }
}
