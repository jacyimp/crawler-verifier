<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange\Update;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Catalog\CrawlerDefinition;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerVerifierConfig;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Exception\InvalidIpRangeDataException;
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

#[CoversClass(IpRangeUpdater::class)]
#[UsesClass(CrawlerVerifierConfig::class)]
#[UsesClass(BuiltInCrawlerCatalog::class)]
#[UsesClass(CrawlerDefinition::class)]
#[UsesClass(IpRangeFeed::class)]
#[UsesClass(IpRangeUpdateResult::class)]
#[UsesClass(JsonIpRangeParser::class)]
#[UsesClass(PsrCacheIpRangeSource::class)]
#[UsesClass(InvalidConfigurationException::class)]
#[UsesClass(InvalidIpRangeDataException::class)]
#[UsesClass(IpRangeUpdateException::class)]
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
            $cache->getArray(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                ),
            )['ranges'],
        );

        self::assertIsInt(
            $cache->getArray(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                ),
            )['refreshed_at'],
        );
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
            $cache->getArray(
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
            $cache->getArray($key)['ranges'],
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
            $cache->getArray($key)['ranges'],
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
    public function itDerivesDefaultFeedsFromTheBuiltInCatalog(): void
    {
        $updater = IpRangeUpdater::create(
            new CrawlerVerifierConfig(
                cache: new ArrayCache(),
            ),
        );

        $result = $updater->refresh(
            Crawler::PinterestBot,
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
            InvalidConfigurationException::class,
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
            ),
            cacheKeyPrefix: $cacheKeyPrefix,
        );
    }

    /**
     * @param array<string, string> $responses
     */
    private function fetcher(
        array $responses,
    ): IpRangeFetcher {
        return new class($responses) implements IpRangeFetcher {
            /**
             * @param array<string, string> $responses
             */
            public function __construct(
                private readonly array $responses,
            ) {
            }

            public function fetch(
                string $url,
            ): string {
                return $this->responses[$url];
            }
        };
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
