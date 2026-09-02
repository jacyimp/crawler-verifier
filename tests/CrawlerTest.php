<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Crawler::class)]
#[UsesClass(CrawlerProviderRegistry::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
#[UsesClass(OpenAiProvider::class)]
#[UsesClass(GoogleProvider::class)]
#[UsesClass(BingProvider::class)]
#[UsesClass(AppleProvider::class)]
#[UsesClass(BaiduProvider::class)]
#[UsesClass(DuckDuckGoProvider::class)]
#[UsesClass(PinterestProvider::class)]
#[UsesClass(PerplexityProvider::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
final class CrawlerTest extends TestCase
{
    #[Test]
    public function itHasAVerificationProviderForEveryCrawler(): void
    {
        $providers = CrawlerProviderRegistry::defaults(
            $this->rangeSource(),
        )->all();

        foreach (Crawler::cases() as $crawler) {
            $supported = false;

            foreach ($providers as $provider) {
                if ($provider->supports($crawler)) {
                    $supported = true;

                    break;
                }
            }

            self::assertTrue(
                $supported,
                sprintf(
                    'Crawler "%s" has no verification provider.',
                    $crawler->value,
                ),
            );
        }
    }

    private function rangeSource(): IpRangeSource
    {
        return new class implements IpRangeSource {
            public function rangesFor(
                Crawler $crawler,
            ): ?array {
                return null;
            }
        };
    }
}
