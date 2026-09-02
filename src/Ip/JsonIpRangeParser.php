<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Ip;

use JsonException;
use RuntimeException;

final class JsonIpRangeParser
{
    /**
     * @return list<string>
     */
    public function parse(string $json): array
    {
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to parse IP range data.',
                previous: $exception,
            );
        }

        if (!is_array($data) || !isset($data['prefixes']) || !is_array($data['prefixes'])) {
            throw new RuntimeException('Invalid IP range data.');
        }

        $ranges = [];

        foreach ($data['prefixes'] as $prefix) {
            if (!is_array($prefix)) {
                throw new RuntimeException('Invalid IP range prefix.');
            }

            $range = $prefix['ipv4Prefix']
                ?? $prefix['ipv6Prefix']
                ?? null;

            if (!is_string($range) || !$this->isValidRange($range)) {
                throw new RuntimeException('Invalid IP range prefix.');
            }

            $ranges[] = $range;
        }

        return $ranges;
    }

    private function isValidRange(string $range): bool
    {
        $parts = explode('/', $range, 2);

        if (
            count($parts) !== 2
            || !ctype_digit($parts[1])
        ) {
            return false;
        }

        [$ip, $prefix] = $parts;

        $binary = inet_pton($ip);

        if ($binary === false) {
            return false;
        }

        return (int) $prefix <= strlen($binary) * 8;
    }
}
