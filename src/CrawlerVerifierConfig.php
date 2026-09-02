<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use Psr\SimpleCache\CacheInterface;

final readonly class CrawlerVerifierConfig
{
    /**
     * @param list<string> $localRangeDirectories
     */
    public function __construct(
        public ?CacheInterface $cache = null,
        public array $localRangeDirectories = [],
        public string $cacheKeyPrefix = 'crawler_verifier',
        public int $dnsCacheTtlSeconds = 3600,
        public int $dnsNegativeCacheTtlSeconds = 300,
    ) {
        if (
            $this->cacheKeyPrefix === ''
            || preg_match('/^[A-Za-z0-9_.]+$/', $this->cacheKeyPrefix) !== 1
        ) {
            throw InvalidConfigurationException::invalidCacheKeyPrefix();
        }

        if ($this->dnsCacheTtlSeconds < 0) {
            throw InvalidConfigurationException::negativeDnsCacheTtl();
        }

        if ($this->dnsNegativeCacheTtlSeconds < 0) {
            throw InvalidConfigurationException::negativeDnsNegativeCacheTtl();
        }
    }
}
