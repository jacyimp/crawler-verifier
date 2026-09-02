<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Catalog\CrawlerDefinition;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\IpRange\IpRangeMatcher;
use JacyImp\CrawlerVerifier\IpRange\Source\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\BuiltInCrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(BuiltInCrawlerCatalog::class)]
#[UsesClass(CrawlerDefinition::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
final class BuiltInCrawlerProviderVerificationTest extends TestCase
{
    #[Test]
    public function itVerifiesUsingPublishedIpRanges(): void
    {
        $provider = $this->provider(
            ranges: [
                Crawler::GPTBot->value => [
                    '192.0.2.0/24',
                ],
            ],
        );

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                Crawler::GPTBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itRejectsAnIpOutsidePublishedRanges(): void
    {
        $provider = $this->provider(
            ranges: [
                Crawler::GPTBot->value => [
                    '192.0.2.0/24',
                ],
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::GPTBot,
                '203.0.113.42',
            ),
        );
    }

    #[Test]
    public function itFallsBackToDnsAfterAnIpRangeMiss(): void
    {
        $provider = $this->provider(
            ranges: [
                Crawler::Googlebot->value => [
                    '192.0.2.0/24',
                ],
            ],
            reverse: 'crawl.googlebot.com',
            forward: [
                '203.0.113.42',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Googlebot,
                '203.0.113.42',
            ),
        );
    }

    #[Test]
    public function itVerifiesDnsOnlyCrawlers(): void
    {
        $provider = $this->provider(
            reverse: 'crawler.pinterestcrawler.com',
            forward: [
                '192.0.2.42',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::PinterestBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itDoesNotUseIpRangesForDnsOnlyCrawlers(): void
    {
        $provider = $this->provider(
            ranges: [
                Crawler::PinterestBot->value => [
                    '192.0.2.0/24',
                ],
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::PinterestBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itRejectsAnInvalidDnsSuffix(): void
    {
        $provider = $this->provider(
            reverse: 'baidu.com.attacker.example',
            forward: [
                '192.0.2.42',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::BaiduSpider,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyCustomCrawlerIdentities(): void
    {
        $crawler = new class implements CrawlerIdentity {
            public function id(): string
            {
                return 'custom-bot';
            }
        };

        self::assertNull(
            $this->provider()->verify(
                $crawler,
                '192.0.2.42',
            ),
        );
    }

    /**
     * @param array<string, list<string>> $ranges
     * @param list<string> $forward
     */
    private function provider(
        array $ranges = [],
        ?string $reverse = null,
        array $forward = [],
    ): BuiltInCrawlerProvider {
        return new BuiltInCrawlerProvider(
            catalog: BuiltInCrawlerCatalog::defaults(),
            rangeSource: $this->rangeSource($ranges),
            dnsVerifier: new ForwardConfirmedReverseDnsVerifier(
                $this->resolver(
                    reverse: $reverse,
                    forward: $forward,
                ),
            ),
        );
    }

    /**
     * @param array<string, list<string>> $ranges
     */
    private function rangeSource(array $ranges): IpRangeSource
    {
        return new class ($ranges) implements IpRangeSource {
            /**
             * @param array<string, list<string>> $ranges
             */
            public function __construct(
                private readonly array $ranges,
            ) {
            }

            public function rangesFor(
                Crawler $crawler,
            ): ?array {
                return $this->ranges[$crawler->value]
                    ?? null;
            }
        };
    }

    /**
     * @param list<string> $forward
     */
    private function resolver(
        ?string $reverse,
        array $forward,
    ): DnsResolver {
        return new class ($reverse, $forward) implements DnsResolver {
            /**
             * @param list<string> $forward
             */
            public function __construct(
                private readonly ?string $reverse,
                private readonly array $forward,
            ) {
            }

            public function reverse(
                string $ip,
            ): ?string {
                return $this->reverse;
            }

            public function forward(
                string $hostname,
            ): array {
                return $this->forward;
            }
        };
    }
}
