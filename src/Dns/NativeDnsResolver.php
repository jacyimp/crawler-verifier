<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Dns;

final class NativeDnsResolver implements DnsResolver
{
    public function reverse(string $ip): ?string
    {
        $hostname = @gethostbyaddr($ip);

        if ($hostname === false || $hostname === $ip) {
            return null;
        }

        return rtrim($hostname, '.');
    }

    public function forward(string $hostname): array
    {
        $records = @dns_get_record(
            $hostname,
            DNS_A | DNS_AAAA,
        );

        if ($records === false) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            if (isset($record['ip']) && is_string($record['ip'])) {
                $addresses[] = $record['ip'];
            }

            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }
}
