<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Source;

use JacyImp\CrawlerVerifier\Crawler;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;

final readonly class PsrCacheIpRangeSource implements IpRangeSource
{
    public function __construct(
        private CacheInterface $cache,
        private string $cacheKeyPrefix = 'crawler_verifier',
    ) {
    }

    /**
     * @return list<string>|null
     */
    public function rangesFor(Crawler $crawler): ?array
    {
        $entry = $this->entryFor($crawler);

        if ($entry === null) {
            return null;
        }

        return $entry['ranges'];
    }

    /**
     * @return array{
     *     ranges: non-empty-list<string>,
     *     refreshed_at: int
     * }|null
     */
    public function entryFor(Crawler $crawler): ?array
    {
        try {
            $entry = $this->cache->get(
                self::key(
                    $crawler,
                    $this->cacheKeyPrefix,
                ),
            );
        } catch (CacheException) {
            return null;
        }

        if (
            !is_array($entry)
            || !isset($entry['ranges'])
            || !is_array($entry['ranges'])
            || $entry['ranges'] === []
            || !isset($entry['refreshed_at'])
            || !is_int($entry['refreshed_at'])
        ) {
            return null;
        }

        foreach ($entry['ranges'] as $range) {
            if (!is_string($range)) {
                return null;
            }
        }

        return [
            'ranges' => array_values(
                $entry['ranges'],
            ),
            'refreshed_at' => $entry['refreshed_at'],
        ];
    }

    public static function key(
        Crawler $crawler,
        string $prefix = 'crawler_verifier',
    ): string {
        return sprintf(
            '%s.ranges.%s',
            $prefix,
            $crawler->value,
        );
    }
}
