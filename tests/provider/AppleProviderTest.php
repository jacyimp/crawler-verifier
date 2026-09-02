<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\AppleProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppleProvider::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
final class AppleProviderTest extends TestCase
{
    #[Test]
    public function itIdentifiesApplebot(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::Applebot,
            $provider->identify(
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) '
                . 'AppleWebKit/605.1.15 (KHTML, like Gecko) '
                . 'Version/17.4 Safari/605.1.15 '
                . '(Applebot/0.1; +http://www.apple.com/go/applebot)',
            ),
        );
    }

    #[Test]
    public function itMatchesApplebotCaseInsensitively(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::Applebot,
            $provider->identify(
                'Mozilla/5.0; compatible; applebot/0.1',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyApplebotExtended(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'Applebot-Extended',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialApplebotToken(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'DefinitelyApplebot/1.0',
            ),
        );
    }

    #[Test]
    public function itSupportsApplebot(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::Applebot,
            ),
        );
    }

    #[Test]
    public function itDoesNotSupportOtherCrawlers(): void
    {
        self::assertFalse(
            $this->provider()->supports(
                Crawler::Googlebot,
            ),
        );
    }

    #[Test]
    public function itVerifiesApplebotUsingPublishedIpRanges(): void
    {
        $provider = $this->provider(
            ranges: [
                '17.241.208.160/27',
            ],
        );

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                Crawler::Applebot,
                '17.241.208.170',
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
            reverse: '17-58-101-179.applebot.apple.com',
            forward: [
                '17.58.101.179',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Applebot,
                '17.58.101.179',
            ),
        );
    }

    #[Test]
    public function itCanVerifyUsingDnsWhenNoIpRangesAreAvailable(): void
    {
        $provider = $this->provider(
            ranges: null,
            reverse: '17-58-101-179.applebot.apple.com',
            forward: [
                '17.58.101.179',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::Applebot,
                '17.58.101.179',
            ),
        );
    }

    #[Test]
    public function itRejectsAnInvalidApplebotDnsSuffix(): void
    {
        $provider = $this->provider(
            ranges: [],
            reverse: 'applebot.apple.com.attacker.example',
            forward: [
                '17.58.101.179',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Applebot,
                '17.58.101.179',
            ),
        );
    }

    #[Test]
    public function itRejectsApplebotWhenForwardDnsDoesNotMatch(): void
    {
        $provider = $this->provider(
            ranges: [],
            reverse: '17-58-101-179.applebot.apple.com',
            forward: [
                '203.0.113.42',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Applebot,
                '17.58.101.179',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = $this->provider(
            ranges: [
                '17.0.0.0/8',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '17.58.101.179',
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
    ): AppleProvider {
        return new AppleProvider(
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
