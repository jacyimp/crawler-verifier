<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Ip;

use InvalidArgumentException;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerVerifierConfig;
use JacyImp\CrawlerVerifier\Ip\IpRangeFeed;
use JacyImp\CrawlerVerifier\Ip\IpRangeFetcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeFeedRegistry;
use JacyImp\CrawlerVerifier\Ip\IpRangeUpdater;
use JacyImp\CrawlerVerifier\Ip\IpRangeUpdateResult;
use JacyImp\CrawlerVerifier\Ip\JsonIpRangeParser;
use JacyImp\CrawlerVerifier\Ip\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\Tests\Support\ArrayCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(IpRangeUpdater::class)]
#[UsesClass(CrawlerVerifierConfig::class)]
#[UsesClass(IpRangeFeed::class)]
#[UsesClass(IpRangeFeedRegistry::class)]
#[UsesClass(IpRangeUpdateResult::class)]
#[UsesClass(JsonIpRangeParser::class)]
#[UsesClass(PsrCacheIpRangeSource::class)]
final class IpRangeUpdaterTest extends TestCase
{
    #[Test]
    public function itRefreshesIpRangesIntoCache(): void
    {
        $cache = new ArrayCache();

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => $this->rangesJson(
                    '192.0.2.0/24',
                ),
            ],
        );

        $result = $updater->refresh();

        self::assertTrue(
            $result->successful(),
        );

        self::assertSame(
            ['192.0.2.0/24'],
            $cache->get(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                ),
            )['ranges'],
        );

        self::assertIsInt($cache->get(
            PsrCacheIpRangeSource::key(Crawler::GPTBot),
        )['refreshed_at']);
    }

    #[Test]
    public function itUsesTheConfiguredCacheKeyPrefix(): void
    {
        $cache = new ArrayCache();

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => $this->rangesJson(
                    '192.0.2.0/24',
                ),
            ],
            cacheKeyPrefix: 'my_app',
        );

        $updater->refresh();

        self::assertSame(
            ['192.0.2.0/24'],
            $cache->get(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                    'my_app',
                ),
            )['ranges'],
        );
    }

    #[Test]
    public function itCanRefreshASingleCrawler(): void
    {
        $cache = new ArrayCache();

        $updater = new IpRangeUpdater(
            cache: $cache,
            feeds: [
                new IpRangeFeed(
                    Crawler::GPTBot,
                    'https://example.com/gptbot.json',
                ),
                new IpRangeFeed(
                    Crawler::OaiSearchBot,
                    'https://example.com/searchbot.json',
                ),
            ],
            fetcher: $this->fetcher(
                responses: [
                    'https://example.com/gptbot.json' => $this->rangesJson(
                        '192.0.2.0/24',
                    ),
                    'https://example.com/searchbot.json' => $this->rangesJson(
                        '203.0.113.0/24',
                    ),
                ],
            ),
        );

        $updater->refresh(
            Crawler::GPTBot,
        );

        self::assertTrue(
            $cache->has(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                ),
            ),
        );

        self::assertFalse(
            $cache->has(
                PsrCacheIpRangeSource::key(
                    Crawler::OaiSearchBot,
                ),
            ),
        );
    }

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
            $cache->get(
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
            InvalidArgumentException::class,
        );

        $this->updater(
            cache: new ArrayCache(),
            responses: [],
        )->refreshIfStale(-1);
    }

    #[Test]
    public function itDoesNotReplaceExistingRangesWithInvalidData(): void
    {
        $cache = new ArrayCache();

        $key = PsrCacheIpRangeSource::key(
            Crawler::GPTBot,
        );

        $cache->set(
            $key,
            [
                'ranges' => ['192.0.2.0/24'],
                'refreshed_at' => time() - 3600,
            ],
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => '{"prefixes":["broken"]}',
            ],
        );

        $result = $updater->refresh();

        self::assertFalse(
            $result->successful(),
        );

        self::assertSame(
            ['192.0.2.0/24'],
            $cache->get($key)['ranges'],
        );
    }

    #[Test]
    public function itDoesNotReplaceExistingRangesWithAnEmptyFeed(): void
    {
        $cache = new ArrayCache();

        $key = PsrCacheIpRangeSource::key(
            Crawler::GPTBot,
        );

        $cache->set(
            $key,
            [
                'ranges' => ['192.0.2.0/24'],
                'refreshed_at' => time() - 3600,
            ],
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json' => '{"prefixes":[]}',
            ],
        );

        $result = $updater->refresh();

        self::assertFalse(
            $result->successful(),
        );

        self::assertSame(
            ['192.0.2.0/24'],
            $cache->get($key)['ranges'],
        );
    }

    #[Test]
    public function itReportsACrawlerWithoutAConfiguredFeed(): void
    {
        $updater = new IpRangeUpdater(
            cache: new ArrayCache(),
            feeds: [],
            fetcher: $this->fetcher(
                responses: [],
            ),
        );

        $result = $updater->refresh(
            Crawler::PinterestBot,
        );

        self::assertFalse(
            $result->successful(),
        );
        self::assertTrue(
            $result->failed(
                Crawler::PinterestBot,
            ),
        );
    }

    #[Test]
    public function itRequiresACacheWhenUsingTheDefaultUpdater(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        IpRangeUpdater::create(
            new CrawlerVerifierConfig(),
        );
    }
    #[Test]
    public function itLeavesThePreviousCachedEntryUntouchedWhenRefreshFails(): void
    {
        $cache = new ArrayCache();

        $key = PsrCacheIpRangeSource::key(
            Crawler::GPTBot,
        );

        $previous = [
            'ranges' => [
                '192.0.2.0/24',
            ],
            'refreshed_at' => time() - 3600,
        ];

        $cache->set(
            $key,
            $previous,
        );

        $updater = $this->updater(
            cache: $cache,
            responses: [
                'https://example.com/gptbot.json'
                => '{"prefixes":["broken"]}',
            ],
        );

        $result = $updater->refresh();

        self::assertFalse(
            $result->successful(),
        );

        self::assertSame(
            $previous,
            $cache->get($key),
        );
    }

    /**
     * @param array<string, string> $responses
     */
    private function updater(
        ArrayCache $cache,
        array $responses,
        ?stdClass $state = null,
        string $cacheKeyPrefix = 'crawler_verifier',
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
            cacheKeyPrefix: $cacheKeyPrefix,
        );
    }

    /**
     * @param array<string, string> $responses
     */
    private function fetcher(
        array $responses,
        ?stdClass $state = null,
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
                private readonly ?stdClass $state,
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

    private function state(): stdClass
    {
        return (object) [
            'fetchCalls' => 0,
        ];
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
