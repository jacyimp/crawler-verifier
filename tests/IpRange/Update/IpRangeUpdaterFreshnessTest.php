<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange\Update;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Exception\IpRangeUpdateException;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeFeed;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeFetcher;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdater;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdateResult;
use JacyImp\CrawlerVerifier\IpRange\JsonIpRangeParser;
use JacyImp\CrawlerVerifier\IpRange\Source\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\Tests\Support\ArrayCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(IpRangeFeed::class)]
#[UsesClass(IpRangeUpdateResult::class)]
#[UsesClass(JsonIpRangeParser::class)]
#[UsesClass(PsrCacheIpRangeSource::class)]
#[UsesClass(InvalidConfigurationException::class)]
#[UsesClass(IpRangeUpdateException::class)]
final class IpRangeUpdaterFreshnessTest extends TestCase
{
    #[Test]
    public function itSkipsFreshRanges(): void
    {
        $cache = new ArrayCache();
        $state = $this->state();

        $cache->set(
            PsrCacheIpRangeSource::key(Crawler::GPTBot),
            [
                'ranges' => [
                    '192.0.2.0/24',
                ],
                'refreshed_at' => time(),
            ],
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => $this->rangesJson(
                    '203.0.113.0/24',
                ),
            ],
            state: $state,
        );

        $result = $updater->refreshIfStale(
            3600,
        );

        self::assertTrue(
            $result->successful(),
        );
        self::assertTrue(
            $result->wasSkipped(
                Crawler::GPTBot,
            ),
        );
        self::assertFalse(
            $result->wasUpdated(
                Crawler::GPTBot,
            ),
        );
        self::assertSame(
            0,
            $state->fetchCalls,
        );
    }

    #[Test]
    public function itRefreshesStaleRanges(): void
    {
        $cache = new ArrayCache();
        $state = $this->state();

        $cache->set(
            PsrCacheIpRangeSource::key(Crawler::GPTBot),
            [
                'ranges' => [
                    '192.0.2.0/24',
                ],
                'refreshed_at' => time() - 7200,
            ],
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => $this->rangesJson(
                    '203.0.113.0/24',
                ),
            ],
            state: $state,
        );

        $result = $updater->refreshIfStale(
            3600,
        );

        self::assertTrue(
            $result->wasUpdated(
                Crawler::GPTBot,
            ),
        );
        self::assertSame(
            1,
            $state->fetchCalls,
        );

        self::assertSame(
            ['203.0.113.0/24'],
            $cache->getArray(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                ),
            )['ranges'],
        );
    }

    #[Test]
    public function itRefreshesWhenFreshnessMetadataIsMissing(): void
    {
        $cache = new ArrayCache();
        $state = $this->state();

        $cache->set(
            PsrCacheIpRangeSource::key(Crawler::GPTBot),
            [
                'ranges' => ['192.0.2.0/24'],
            ],
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => $this->rangesJson(
                    '203.0.113.0/24',
                ),
            ],
            state: $state,
        );

        $result = $updater->refreshIfStale(
            3600,
        );

        self::assertTrue(
            $result->wasUpdated(
                Crawler::GPTBot,
            ),
        );
        self::assertSame(
            1,
            $state->fetchCalls,
        );
    }

    #[Test]
    public function itRefreshesWhenCachedRangesAreMissing(): void
    {
        $cache = new ArrayCache();
        $state = $this->state();

        $cache->set(
            PsrCacheIpRangeSource::key(Crawler::GPTBot),
            [
                'refreshed_at' => time(),
            ],
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => $this->rangesJson(
                    '192.0.2.0/24',
                ),
            ],
            state: $state,
        );

        $result = $updater->refreshIfStale(
            3600,
        );

        self::assertTrue(
            $result->wasUpdated(
                Crawler::GPTBot,
            ),
        );
        self::assertSame(
            1,
            $state->fetchCalls,
        );
    }

    #[Test]
    public function itRejectsANegativeMaximumAge(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        $this->updater(
            cache: new ArrayCache(),
            responses: [],
        )->refreshIfStale(-1);
    }

    /**
     * @param array<string, string> $responses
     */
    private function updater(
        ArrayCache $cache,
        array $responses,
        ?IpRangeFetcherState $state = null,
    ): IpRangeUpdater {
        return new IpRangeUpdater(
            cache: $cache,
            feeds: [
                new IpRangeFeed(
                    Crawler::GPTBot,
                    'https://example.com/gptbot.json',
                ),
            ],
            fetcher: $this->fetcher(
                responses: $responses,
                state: $state,
            ),
        );
    }

    /**
     * @param array<string, string> $responses
     */
    private function fetcher(
        array $responses,
        ?IpRangeFetcherState $state = null,
    ): IpRangeFetcher {
        return new class(
            $responses,
            $state,
        ) implements IpRangeFetcher {
            /**
             * @param array<string, string> $responses
             */
            public function __construct(
                private readonly array $responses,
                private readonly ?IpRangeFetcherState $state,
            ) {
            }

            public function fetch(
                string $url,
            ): string {
                if ($this->state !== null) {
                    ++$this->state->fetchCalls;
                }

                return $this->responses[$url];
            }
        };
    }

    private function state(): IpRangeFetcherState
    {
        return new IpRangeFetcherState();
    }

    private function rangesJson(
        string $range,
    ): string {
        return json_encode(
            [
                'prefixes' => [
                    [
                        'ipv4Prefix' => $range,
                    ],
                ],
            ],
            JSON_THROW_ON_ERROR,
        );
    }
}

final class IpRangeFetcherState
{
    public int $fetchCalls = 0;
}
