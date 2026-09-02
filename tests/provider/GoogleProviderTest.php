<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\GoogleProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GoogleProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
final class GoogleProviderTest extends TestCase
{
    #[Test]
    public function itIdentifiesGooglebot(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::Googlebot,
            $provider->identify(
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            ),
        );
    }

    #[Test]
    public function itIdentifiesGooglebotVariants(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::Googlebot,
            $provider->identify(
                'Googlebot-Image/1.0',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialGooglebotToken(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'DefinitelyNotGooglebot/1.0',
            ),
        );
    }

    #[Test]
    public function itSupportsGooglebot(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::Googlebot,
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
    public function itVerifiesGooglebotUsingPublishedIpRanges(): void
    {
        $provider = $this->provider(
            ranges: [
                '66.249.66.0/24',
            ],
        );

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                Crawler::Googlebot,
                '66.249.66.1',
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
            reverse: 'crawl-66-249-66-1.googlebot.com',
            forward: [
                '66.249.66.1',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Googlebot,
                '66.249.66.1',
            ),
        );
    }

    #[Test]
    public function itCanVerifyUsingDnsWhenNoIpRangesAreAvailable(): void
    {
        $provider = $this->provider(
            ranges: null,
            reverse: 'crawl-66-249-66-1.googlebot.com',
            forward: [
                '66.249.66.1',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Googlebot,
                '66.249.66.1',
            ),
        );
    }

    #[Test]
    public function itRejectsGooglebotWhenBothVerificationMethodsFail(): void
    {
        $provider = $this->provider(
            ranges: [
                '192.0.2.0/24',
            ],
            reverse: 'attacker.example',
            forward: [
                '66.249.66.1',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '66.249.66.1',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = $this->provider(
            ranges: [
                '66.249.66.0/24',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::GPTBot,
                '66.249.66.1',
            ),
        );
    }

    /**
     * @param list<string>|null $ranges
     * @param list<string> $forward
     */
    private function provider(
        ?array $ranges = null,
        ?string $reverse = null,
        array $forward = [],
    ): GoogleProvider {
        return new GoogleProvider(
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
