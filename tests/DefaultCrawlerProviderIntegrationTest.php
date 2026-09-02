<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerVerifier;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\DirectoryIpRangeSource;
use JacyImp\CrawlerVerifier\Ip\FallbackIpRangeSource;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Provider\AppleProvider;
use JacyImp\CrawlerVerifier\Provider\BaiduProvider;
use JacyImp\CrawlerVerifier\Provider\BingProvider;
use JacyImp\CrawlerVerifier\Provider\CrawlerProviderRegistry;
use JacyImp\CrawlerVerifier\Provider\DuckDuckGoProvider;
use JacyImp\CrawlerVerifier\Provider\GoogleProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\OpenAiProvider;
use JacyImp\CrawlerVerifier\Provider\PerplexityProvider;
use JacyImp\CrawlerVerifier\Provider\PinterestProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrawlerVerifier::class)]
#[UsesClass(CrawlerProviderRegistry::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
#[UsesClass(OpenAiProvider::class)]
#[UsesClass(GoogleProvider::class)]
#[UsesClass(BingProvider::class)]
#[UsesClass(AppleProvider::class)]
#[UsesClass(DuckDuckGoProvider::class)]
#[UsesClass(PinterestProvider::class)]
#[UsesClass(BaiduProvider::class)]
#[UsesClass(PerplexityProvider::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
#[UsesClass(DirectoryIpRangeSource::class)]
#[UsesClass(FallbackIpRangeSource::class)]
#[UsesClass(IpRangeMatcher::class)]
final class DefaultCrawlerProviderIntegrationTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesDefaultCrawlers(
        string $userAgent,
        Crawler $expectedCrawler,
    ): void {
        self::assertSame(
            $expectedCrawler,
            CrawlerVerifier::create()->identify(
                $userAgent,
            ),
        );
    }

    /**
     * @return iterable<string, array{string, Crawler}>
     */
    public static function userAgents(): iterable
    {
        yield 'GPTBot' => [
            'Mozilla/5.0; compatible; GPTBot/1.1',
            Crawler::GPTBot,
        ];

        yield 'OAI-SearchBot' => [
            'Mozilla/5.0; compatible; OAI-SearchBot/1.0',
            Crawler::OaiSearchBot,
        ];

        yield 'OAI-AdsBot' => [
            'Mozilla/5.0; compatible; OAI-AdsBot/1.0',
            Crawler::OaiAdsBot,
        ];

        yield 'Googlebot' => [
            'Mozilla/5.0; compatible; Googlebot/2.1',
            Crawler::Googlebot,
        ];

        yield 'bingbot' => [
            'Mozilla/5.0; compatible; bingbot/2.0',
            Crawler::Bingbot,
        ];

        yield 'Applebot' => [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) '
            . 'AppleWebKit/605.1.15 (KHTML, like Gecko) '
            . 'Version/17.4 Safari/605.1.15 '
            . '(Applebot/0.1; +http://www.apple.com/go/applebot)',
            Crawler::Applebot,
        ];

        yield 'DuckDuckBot' => [
            'DuckDuckBot/1.1; (+http://duckduckgo.com/duckduckbot.html)',
            Crawler::DuckDuckBot,
        ];

        yield 'Pinterestbot' => [
            'Mozilla/5.0 (compatible; Pinterestbot/1.0; +https://www.pinterest.com/bot.html)',
            Crawler::PinterestBot,
        ];

        yield 'Baiduspider' => [
            'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
            Crawler::BaiduSpider,
        ];

        yield 'Baiduspider-render' => [
            'Mozilla/5.0 (compatible; Baiduspider-render/2.0; +http://www.baidu.com/search/spider.html)',
            Crawler::BaiduSpider,
        ];

        yield 'PerplexityBot' => [
            'Mozilla/5.0 AppleWebKit/537.36 '
            . '(KHTML, like Gecko; compatible; PerplexityBot/1.0; '
            . '+https://perplexity.ai/perplexitybot)',
            Crawler::PerplexityBot,
        ];

        yield 'Perplexity-User' => [
            'Mozilla/5.0 AppleWebKit/537.36 '
            . '(KHTML, like Gecko; compatible; Perplexity-User/1.0; '
            . '+https://perplexity.ai/perplexity-user)',
            Crawler::PerplexityUser,
        ];
    }
}
