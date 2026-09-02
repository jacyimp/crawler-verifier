<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\AppleProvider;
use JacyImp\CrawlerVerifier\Provider\BaiduProvider;
use JacyImp\CrawlerVerifier\Provider\BingProvider;
use JacyImp\CrawlerVerifier\Provider\CrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\CrawlerProviderRegistry;
use JacyImp\CrawlerVerifier\Provider\DuckDuckGoProvider;
use JacyImp\CrawlerVerifier\Provider\GoogleProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\OpenAiProvider;
use JacyImp\CrawlerVerifier\Provider\PerplexityProvider;
use JacyImp\CrawlerVerifier\Provider\PinterestProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrawlerProviderRegistry::class)]
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
final class CrawlerProviderRegistryTest extends TestCase
{
    #[Test]
    public function itReturnsRegisteredProviders(): void
    {
        $provider = $this->provider();

        $registry = new CrawlerProviderRegistry([
            $provider,
        ]);

        self::assertSame(
            [$provider],
            $registry->all(),
        );
    }

    #[Test]
    public function itProvidesTheDefaultProviderCatalog(): void
    {
        $registry = CrawlerProviderRegistry::defaults(
            $this->rangeSource(),
        );

        self::assertSame(
            [
                OpenAiProvider::class,
                GoogleProvider::class,
                BingProvider::class,
                AppleProvider::class,
                DuckDuckGoProvider::class,
                PinterestProvider::class,
                BaiduProvider::class,
                PerplexityProvider::class,
            ],
            array_map(
                static fn (CrawlerProvider $provider): string => $provider::class,
                $registry->all(),
            ),
        );
    }

    private function provider(): CrawlerProvider
    {
        return new class implements CrawlerProvider {
            public function identify(
                string $userAgent,
            ): ?Crawler {
                return null;
            }

            public function supports(
                Crawler $crawler,
            ): bool {
                return false;
            }

            public function verify(
                Crawler $crawler,
                string $ip,
            ): ?VerificationMethod {
                return null;
            }
        };
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
