<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\BingProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BingProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
final class BingProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesBingbot(
        string $userAgent,
    ): void {
        $provider = $this->provider();

        self::assertSame(
            Crawler::Bingbot,
            $provider->identify($userAgent),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialBingbotToken(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'DefinitelyNotBingbot/1.0',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAnUnknownUserAgent(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'Mozilla/5.0 Firefox/142.0',
            ),
        );
    }

    #[Test]
    public function itSupportsBingbot(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::Bingbot,
            ),
        );
    }

    #[Test]
    public function itDoesNotSupportOtherCrawlers(): void
    {
        self::assertFalse(
            $this->provider()->supports(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itVerifiesBingbotUsingPublishedIpRanges(): void
    {
        $provider = $this->provider(
            ranges: [
                '157.55.39.0/24',
            ],
        );

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                Crawler::Bingbot,
                '157.55.39.1',
            ),
        );
    }

    #[Test]
    public function itFallsBackToForwardConfirmedReverseDns(): void
    {
        $provider = $this->provider(
            ranges: [
                '192.0.2.0/24',
            ],
            reverse: 'msnbot-157-55-39-1.search.msn.com',
            forward: [
                '157.55.39.1',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Bingbot,
                '157.55.39.1',
            ),
        );
    }

    #[Test]
    public function itCanVerifyUsingDnsWhenNoIpRangesAreAvailable(): void
    {
        $provider = $this->provider(
            ranges: null,
            reverse: 'msnbot-157-55-39-1.search.msn.com',
            forward: [
                '157.55.39.1',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Bingbot,
                '157.55.39.1',
            ),
        );
    }

    #[Test]
    public function itRejectsBingbotWhenTheDnsSuffixIsInvalid(): void
    {
        $provider = $this->provider(
            ranges: [],
            reverse: 'bingbot.attacker.example',
            forward: [
                '157.55.39.1',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Bingbot,
                '157.55.39.1',
            ),
        );
    }

    #[Test]
    public function itRejectsBingbotWhenForwardDnsDoesNotMatchTheOriginalIp(): void
    {
        $provider = $this->provider(
            ranges: [],
            reverse: 'msnbot-157-55-39-1.search.msn.com',
            forward: [
                '203.0.113.42',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Bingbot,
                '157.55.39.1',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = $this->provider(
            ranges: [
                '157.55.39.0/24',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::GPTBot,
                '157.55.39.1',
            ),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function userAgents(): iterable
    {
        yield 'bingbot' => [
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        ];

        yield 'bingbot uppercase' => [
            'Mozilla/5.0 (compatible; BingBot/2.0)',
        ];

        yield 'legacy msnbot' => [
            'msnbot/2.0b (+http://search.msn.com/msnbot.htm)',
        ];
    }

    /**
     * @param list<string>|null $ranges
     * @param list<string> $forward
     */
    private function provider(
        ?array $ranges = null,
        ?string $reverse = null,
        array $forward = [],
    ): BingProvider {
        return new BingProvider(
            rangeSource: $this->rangeSource(
                $ranges,
            ),
            dnsVerifier: new ForwardConfirmedReverseDnsVerifier(
                $this->resolver(
                    reverse: $reverse,
                    forward: $forward,
                ),
            ),
        );
    }

    /**
     * @param list<string>|null $ranges
     */
    private function rangeSource(
        ?array $ranges,
    ): IpRangeSource {
        return new class($ranges) implements IpRangeSource {
            /**
             * @param list<string>|null $ranges
             */
            public function __construct(
                private readonly ?array $ranges,
            ) {
            }

            public function rangesFor(
                Crawler $crawler,
            ): ?array {
                return $this->ranges;
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
        return new class(
            $reverse,
            $forward,
        ) implements DnsResolver {
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
