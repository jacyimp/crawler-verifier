<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Catalog\CrawlerDefinition;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\IpRange\IpRangeMatcher;
use JacyImp\CrawlerVerifier\IpRange\Source\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\BuiltInCrawlerProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(BuiltInCrawlerCatalog::class)]
#[UsesClass(CrawlerDefinition::class)]
#[UsesClass(IpRangeMatcher::class)]
final class BuiltInCrawlerProviderIdentificationTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesBuiltInCrawlers(
        string $userAgent,
        Crawler $crawler,
    ): void {
        self::assertSame(
            $crawler,
            $this->provider()->identify($userAgent),
        );
    }

    #[Test]
    public function itDoesNotIdentifyPartialTokens(): void
    {
        self::assertNull(
            $this->provider()->identify(
                'DefinitelyNotGooglebot/1.0',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyUnknownUserAgents(): void
    {
        self::assertNull(
            $this->provider()->identify(
                'Mozilla/5.0 Firefox/142.0',
            ),
        );
    }

    #[Test]
    public function itSupportsBuiltInCrawlerIdentities(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::Googlebot,
            ),
        );
    }

    #[Test]
    public function itDoesNotSupportCustomCrawlerIdentities(): void
    {
        $crawler = new class implements CrawlerIdentity {
            public function id(): string
            {
                return 'custom-bot';
            }
        };

        self::assertFalse(
            $this->provider()->supports($crawler),
        );
    }

    /**
     * @return iterable<string, array{string, Crawler}>
     */
    public static function userAgents(): iterable
    {
        yield 'Googlebot' => [
            'Mozilla/5.0; compatible; Googlebot/2.1',
            Crawler::Googlebot,
        ];

        yield 'Googlebot Image' => [
            'Googlebot-Image/1.0',
            Crawler::Googlebot,
        ];

        yield 'legacy msnbot' => [
            'msnbot/2.0',
            Crawler::Bingbot,
        ];

        yield 'Applebot' => [
            'Applebot/0.1',
            Crawler::Applebot,
        ];

        yield 'DuckDuckBot' => [
            'DuckDuckBot/1.1',
            Crawler::DuckDuckBot,
        ];

        yield 'Pinterestbot' => [
            'Pinterestbot/1.0',
            Crawler::PinterestBot,
        ];

        yield 'legacy Pinterest' => [
            'Pinterest/0.2',
            Crawler::PinterestBot,
        ];

        yield 'Baiduspider render' => [
            'Baiduspider-render/2.0',
            Crawler::BaiduSpider,
        ];

        yield 'GPTBot' => [
            'GPTBot/1.1',
            Crawler::GPTBot,
        ];

        yield 'OAI Search' => [
            'OAI-SearchBot/1.0',
            Crawler::OaiSearchBot,
        ];

        yield 'OAI Ads' => [
            'OAI-AdsBot/1.0',
            Crawler::OaiAdsBot,
        ];

        yield 'PerplexityBot' => [
            'PerplexityBot/1.0',
            Crawler::PerplexityBot,
        ];

        yield 'Perplexity User' => [
            'Perplexity-User/1.0',
            Crawler::PerplexityUser,
        ];

        yield 'case insensitive' => [
            'googlebot/2.1',
            Crawler::Googlebot,
        ];
    }

    private function provider(): BuiltInCrawlerProvider
    {
        return new BuiltInCrawlerProvider(
            catalog: BuiltInCrawlerCatalog::defaults(),
            rangeSource: $this->rangeSource(),
        );
    }

    private function rangeSource(): IpRangeSource
    {
        return new class implements IpRangeSource {
            /**
             * @return list<string>|null
             */
            public function rangesFor(
                Crawler $crawler,
            ): ?array {
                return null;
            }
        };
    }
}
