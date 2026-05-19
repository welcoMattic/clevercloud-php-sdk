<?php

namespace CleverCloud\Sdk\Http;

use CleverCloud\Sdk\Exception\JsonException;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class JsonCodec
{
    public function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new JsonException('Unable to encode value as JSON: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public function decode(string $json): array
    {
        if ('' === $json) {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException('Unable to decode JSON: '.$e->getMessage(), 0, $e);
        }

        if (!\is_array($decoded)) {
            throw new JsonException('Expected a JSON object or array, got '.get_debug_type($decoded));
        }

        return $decoded;
    }
}
