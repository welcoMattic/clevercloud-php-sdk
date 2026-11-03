<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * An add-on backup record returned by `/v2/backups/{ownerId}/{realId}`.
 *
 * The `downloadUrl` is a pre-signed link that expires: fetch the backup
 * immediately rather than storing the URL.
 */
final readonly class Backup
{
    public function __construct(
        #[MapFrom(property: 'backup_id')]
        public string $id,
        #[MapFrom(property: 'creation_date')]
        public string $createdAt,
        #[MapFrom(property: 'entity_id')]
        public ?string $entityId = null,
        public ?string $status = null,
        #[MapFrom(property: 'delete_at')]
        public ?string $deleteAt = null,
        public ?string $filename = null,
        #[MapFrom(property: 'download_url')]
        public ?string $downloadUrl = null,
    ) {
    }
}
