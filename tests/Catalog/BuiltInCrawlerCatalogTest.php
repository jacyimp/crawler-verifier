<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Catalog;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Catalog\CrawlerDefinition;
use JacyImp\CrawlerVerifier\Crawler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BuiltInCrawlerCatalog::class)]
#[UsesClass(CrawlerDefinition::class)]
final class BuiltInCrawlerCatalogTest extends TestCase
{
    #[Test]
    public function itContainsADefinitionForEveryBuiltInCrawler(): void
    {
        $catalog = BuiltInCrawlerCatalog::defaults();

        foreach (Crawler::cases() as $crawler) {
            self::assertNotNull(
                $catalog->find($crawler),
                sprintf(
                    'Crawler "%s" has no built-in definition.',
                    $crawler->value,
                ),
            );
        }
    }

    #[Test]
    public function itExposesOnlyDefinitionsWithIpRangeFeeds(): void
    {
        $crawlers = array_map(
            static fn (CrawlerDefinition $definition): Crawler => $definition->crawler,
            BuiltInCrawlerCatalog::defaults()->withIpRangeFeed(),
        );

        self::assertContains(Crawler::Googlebot, $crawlers);
        self::assertContains(Crawler::GPTBot, $crawlers);
        self::assertContains(Crawler::PerplexityUser, $crawlers);
        self::assertNotContains(Crawler::PinterestBot, $crawlers);
        self::assertNotContains(Crawler::BaiduSpider, $crawlers);
    }

    #[Test]
    public function itKeepsVerificationMetadataTogether(): void
    {
        $google = BuiltInCrawlerCatalog::defaults()->find(
            Crawler::Googlebot,
        );

        self::assertNotNull($google);
        self::assertSame(
            [
                'Googlebot',
                'Googlebot-Image',
                'Googlebot-News',
                'Googlebot-Video',
            ],
            $google->userAgentTokens,
        );
        self::assertSame(
            'https://developers.google.com/static/crawling/ipranges/common-crawlers.json',
            $google->ipRangeFeedUrl,
        );
        self::assertSame(
            ['googlebot.com'],
            $google->dnsSuffixes,
        );
    }
}
