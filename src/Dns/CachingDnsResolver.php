<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Dns;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Throwable;

final readonly class CachingDnsResolver implements DnsResolver
{
    public function __construct(
        private DnsResolver $resolver,
        private CacheInterface $cache,
        private int $positiveTtlSeconds = 3600,
        private int $negativeTtlSeconds = 300,
        private string $cacheKeyPrefix = 'crawler_verifier',
    ) {
        if ($this->positiveTtlSeconds < 0) {
            throw new InvalidArgumentException(
                'Positive DNS cache TTL cannot be negative.',
            );
        }

        if ($this->negativeTtlSeconds < 0) {
            throw new InvalidArgumentException(
                'Negative DNS cache TTL cannot be negative.',
            );
        }
    }

    public function reverse(string $ip): ?string
    {
        $key = sprintf(
            '%s.dns.reverse.%s',
            $this->cacheKeyPrefix,
            hash(
                'sha256',
                $this->normalizeIp($ip),
            ),
        );

        $miss = new stdClass();

        try {
            $cached = $this->cache->get(
                $key,
                $miss,
            );

            if ($cached !== $miss) {
                return is_string($cached)
                    ? $cached
                    : null;
            }
        } catch (Throwable) {
            // Cache failures must not prevent DNS verification.
        }

        $hostname = $this->resolver->reverse(
            $ip,
        );

        $this->store(
            key: $key,
            value: $hostname,
            ttl: $hostname === null
                ? $this->negativeTtlSeconds
                : $this->positiveTtlSeconds,
        );

        return $hostname;
    }

    public function forward(string $hostname): array
    {
        $key = sprintf(
            '%s.dns.forward.%s',
            $this->cacheKeyPrefix,
            hash(
                'sha256',
                $this->normalizeHostname(
                    $hostname,
                ),
            ),
        );

        $miss = new stdClass();

        try {
            $cached = $this->cache->get(
                $key,
                $miss,
            );

            if ($cached !== $miss) {
                if (!is_array($cached)) {
                    return [];
                }

                return array_values(array_filter(
                    $cached,
                    static fn (mixed $ip): bool => is_string($ip),
                ));
            }
        } catch (Throwable) {
            // Cache failures must not prevent DNS verification.
        }

        $addresses = $this->resolver->forward(
            $hostname,
        );

        $this->store(
            key: $key,
            value: $addresses,
            ttl: $addresses === []
                ? $this->negativeTtlSeconds
                : $this->positiveTtlSeconds,
        );

        return $addresses;
    }

    private function store(
        string $key,
        mixed $value,
        int $ttl,
    ): void {
        try {
            $this->cache->set(
                $key,
                $value,
                $ttl,
            );
        } catch (Throwable) {
            // Cache failures are non-fatal.
        }
    }

    private function normalizeIp(string $ip): string
    {
        $binary = inet_pton($ip);

        if ($binary === false) {
            return $ip;
        }

        return bin2hex($binary);
    }

    private function normalizeHostname(
        string $hostname,
    ): string {
        return strtolower(
            rtrim($hostname, '.'),
        );
    }
}
