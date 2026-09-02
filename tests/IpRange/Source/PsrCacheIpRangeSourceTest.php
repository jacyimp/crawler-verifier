<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange\Source;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\IpRange\Source\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\Tests\Support\ArrayCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PsrCacheIpRangeSourceTest extends TestCase
{
    #[Test]
    public function itReadsRangesFromCache(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'ranges' => [
                    '192.0.2.0/24',
                    '2001:db8::/32',
                ],
                'refreshed_at' => time(),
            ],
        );

        $source = new PsrCacheIpRangeSource(
            $cache,
        );

        self::assertSame(
            [
                '192.0.2.0/24',
                '2001:db8::/32',
            ],
            $source->rangesFor(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itReturnsTheCompleteCacheEntry(): void
    {
        $cache = new ArrayCache();
        $refreshedAt = time();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'ranges' => [
                    '192.0.2.0/24',
                ],
                'refreshed_at' => $refreshedAt,
            ],
        );

        $source = new PsrCacheIpRangeSource(
            $cache,
        );

        self::assertSame(
            [
                'ranges' => [
                    '192.0.2.0/24',
                ],
                'refreshed_at' => $refreshedAt,
            ],
            $source->entryFor(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itUsesTheConfiguredCacheKeyPrefix(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
                'my_app',
            ),
            [
                'ranges' => [
                    '192.0.2.0/24',
                ],
                'refreshed_at' => time(),
            ],
        );

        $source = new PsrCacheIpRangeSource(
            cache: $cache,
            cacheKeyPrefix: 'my_app',
        );

        self::assertSame(
            [
                '192.0.2.0/24',
            ],
            $source->rangesFor(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itReturnsNullWhenRangesAreNotCached(): void
    {
        $source = new PsrCacheIpRangeSource(
            new ArrayCache(),
        );

        self::assertNull(
            $source->rangesFor(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itRejectsAnEntryWithoutRanges(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'refreshed_at' => time(),
            ],
        );

        self::assertNull(
            (new PsrCacheIpRangeSource($cache))
                ->rangesFor(
                    Crawler::GPTBot,
                ),
        );
    }

    #[Test]
    public function itRejectsAnEntryWithoutFreshnessMetadata(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'ranges' => [
                    '192.0.2.0/24',
                ],
            ],
        );

        self::assertNull(
            (new PsrCacheIpRangeSource($cache))
                ->rangesFor(
                    Crawler::GPTBot,
                ),
        );
    }

    #[Test]
    public function itRejectsEmptyCachedRanges(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'ranges' => [],
                'refreshed_at' => time(),
            ],
        );

        self::assertNull(
            (new PsrCacheIpRangeSource($cache))
                ->rangesFor(
                    Crawler::GPTBot,
                ),
        );
    }

    #[Test]
    public function itRejectsInvalidCachedRanges(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'ranges' => [
                    '192.0.2.0/24',
                    123,
                ],
                'refreshed_at' => time(),
            ],
        );

        self::assertNull(
            (new PsrCacheIpRangeSource($cache))
                ->rangesFor(
                    Crawler::GPTBot,
                ),
        );
    }
}
