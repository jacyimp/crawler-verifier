<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\DuckDuckGoProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DuckDuckGoProvider::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
final class DuckDuckGoProviderTest extends TestCase
{
    #[Test]
    public function itIdentifiesDuckDuckBot(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::DuckDuckBot,
            $provider->identify(
                'DuckDuckBot/1.1; (+http://duckduckgo.com/duckduckbot.html)',
            ),
        );
    }

    #[Test]
    public function itMatchesDuckDuckBotCaseInsensitively(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::DuckDuckBot,
            $provider->identify(
                'duckduckbot/1.1',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialDuckDuckBotToken(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'DefinitelyNotDuckDuckBot/1.1',
            ),
        );
    }

    #[Test]
    public function itSupportsDuckDuckBot(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::DuckDuckBot,
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
    public function itVerifiesDuckDuckBotUsingPublishedIpRanges(): void
    {
        $provider = $this->provider([
            '104.43.54.127/32',
        ]);

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                Crawler::DuckDuckBot,
                '104.43.54.127',
            ),
        );
    }

    #[Test]
    public function itRejectsAnIpOutsideThePublishedRanges(): void
    {
        $provider = $this->provider([
            '104.43.54.127/32',
        ]);

        self::assertNull(
            $provider->verify(
                Crawler::DuckDuckBot,
                '104.43.54.128',
            ),
        );
    }

    #[Test]
    public function itRejectsVerificationWhenNoRangesAreAvailable(): void
    {
        $provider = $this->provider(null);

        self::assertNull(
            $provider->verify(
                Crawler::DuckDuckBot,
                '104.43.54.127',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = $this->provider([
            '104.43.54.127/32',
        ]);

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '104.43.54.127',
            ),
        );
    }

    /**
     * @param list<string>|null $ranges
     */
    private function provider(
        ?array $ranges = null,
    ): DuckDuckGoProvider {
        return new DuckDuckGoProvider(
            $this->rangeSource($ranges),
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
}
