<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Ip;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Ip\IpRangeFeed;
use JacyImp\CrawlerVerifier\Ip\IpRangeFeedRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IpRangeFeedRegistry::class)]
#[UsesClass(IpRangeFeed::class)]
#[UsesClass(InvalidConfigurationException::class)]
final class IpRangeFeedRegistryTest extends TestCase
{
    #[Test]
    public function itReturnsRegisteredFeeds(): void
    {
        $feed = new IpRangeFeed(
            Crawler::GPTBot,
            'https://example.com/gptbot.json',
        );

        $registry = new IpRangeFeedRegistry([
            $feed,
        ]);

        self::assertSame(
            [$feed],
            $registry->all(),
        );
    }

    #[Test]
    public function itFindsAFeedByCrawler(): void
    {
        $feed = new IpRangeFeed(
            Crawler::GPTBot,
            'https://example.com/gptbot.json',
        );

        $registry = new IpRangeFeedRegistry([
            $feed,
        ]);

        self::assertSame(
            $feed,
            $registry->find(Crawler::GPTBot),
        );
    }

    #[Test]
    public function itReturnsNullWhenNoFeedExistsForTheCrawler(): void
    {
        $registry = new IpRangeFeedRegistry([]);

        self::assertNull(
            $registry->find(Crawler::Googlebot),
        );
    }

    #[Test]
    public function itReportsWhetherAFeedExistsForACrawler(): void
    {
        $registry = new IpRangeFeedRegistry([
            new IpRangeFeed(
                Crawler::GPTBot,
                'https://example.com/gptbot.json',
            ),
        ]);

        self::assertTrue(
            $registry->has(Crawler::GPTBot),
        );

        self::assertFalse(
            $registry->has(Crawler::Googlebot),
        );
    }

    #[Test]
    public function itRejectsDuplicateFeedsForTheSameCrawler(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new IpRangeFeedRegistry([
            new IpRangeFeed(
                Crawler::GPTBot,
                'https://example.com/one.json',
            ),
            new IpRangeFeed(
                Crawler::GPTBot,
                'https://example.com/two.json',
            ),
        ]);
    }

    #[Test]
    public function itProvidesTheDefaultFeedCatalog(): void
    {
        $registry = IpRangeFeedRegistry::defaults();

        self::assertTrue(
            $registry->has(Crawler::GPTBot),
        );
        self::assertTrue(
            $registry->has(Crawler::OaiSearchBot),
        );
        self::assertTrue(
            $registry->has(Crawler::OaiAdsBot),
        );
        self::assertTrue(
            $registry->has(Crawler::Googlebot),
        );
        self::assertTrue(
            $registry->has(Crawler::Bingbot),
        );
        self::assertTrue(
            $registry->has(Crawler::Applebot),
        );
        self::assertTrue(
            $registry->has(Crawler::DuckDuckBot),
        );
        self::assertTrue(
            $registry->has(Crawler::PerplexityBot),
        );
        self::assertTrue(
            $registry->has(Crawler::PerplexityUser),
        );
    }
}
