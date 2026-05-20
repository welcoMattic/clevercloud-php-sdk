<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * An add-on backup record returned by
 * `/v4/addon-providers/{providerId}/addons/{addonId}/backups`.
 *
 * The `downloadUrl` is a pre-signed link that expires within minutes — fetch
 * the backup immediately rather than storing the URL.
 */
final readonly class Backup
{
    public function __construct(
        #[MapFrom(property: 'backup_id')]
        public string $id,
        #[MapFrom(property: 'created_at')]
        public string $createdAt,
        public ?string $status = null,
        #[MapFrom(property: 'download_url')]
        public ?string $downloadUrl = null,
        public ?int $size = null,
        public ?string $type = null,
    ) {
    }
}
