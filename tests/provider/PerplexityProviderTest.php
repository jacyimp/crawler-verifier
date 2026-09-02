<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\PerplexityProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PerplexityProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
final class PerplexityProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesPerplexityCrawlers(
        string $userAgent,
        Crawler $crawler,
    ): void {
        $provider = new PerplexityProvider(
            $this->rangeSource(null),
        );

        self::assertSame(
            $crawler,
            $provider->identify($userAgent),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialToken(): void
    {
        $provider = new PerplexityProvider(
            $this->rangeSource(null),
        );

        self::assertNull(
            $provider->identify(
                'DefinitelyNotPerplexityBot/1.0',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAnUnknownUserAgent(): void
    {
        $provider = new PerplexityProvider(
            $this->rangeSource(null),
        );

        self::assertNull(
            $provider->identify(
                'Mozilla/5.0 Firefox/142.0',
            ),
        );
    }

    #[Test]
    #[DataProvider('supportedCrawlers')]
    public function itSupportsPerplexityCrawlers(
        Crawler $crawler,
    ): void {
        $provider = new PerplexityProvider(
            $this->rangeSource(null),
        );

        self::assertTrue(
            $provider->supports($crawler),
        );
    }

    #[Test]
    public function itDoesNotSupportOtherCrawlers(): void
    {
        $provider = new PerplexityProvider(
            $this->rangeSource(null),
        );

        self::assertFalse(
            $provider->supports(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    #[DataProvider('supportedCrawlers')]
    public function itVerifiesPerplexityCrawlersUsingPublishedIpRanges(
        Crawler $crawler,
    ): void {
        $provider = new PerplexityProvider(
            $this->rangeSource([
                '192.0.2.0/24',
            ]),
        );

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                $crawler,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itRejectsAnIpOutsideThePublishedRanges(): void
    {
        $provider = new PerplexityProvider(
            $this->rangeSource([
                '192.0.2.0/24',
            ]),
        );

        self::assertNull(
            $provider->verify(
                Crawler::PerplexityBot,
                '203.0.113.42',
            ),
        );
    }

    #[Test]
    public function itRejectsVerificationWhenNoRangesAreAvailable(): void
    {
        $provider = new PerplexityProvider(
            $this->rangeSource(null),
        );

        self::assertNull(
            $provider->verify(
                Crawler::PerplexityBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = new PerplexityProvider(
            $this->rangeSource([
                '192.0.2.0/24',
            ]),
        );

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '192.0.2.42',
            ),
        );
    }

    /**
     * @return iterable<string, array{string, Crawler}>
     */
    public static function userAgents(): iterable
    {
        yield 'PerplexityBot' => [
            'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)',
            Crawler::PerplexityBot,
        ];

        yield 'Perplexity-User' => [
            'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)',
            Crawler::PerplexityUser,
        ];

        yield 'case insensitive PerplexityBot' => [
            'Mozilla/5.0; compatible; perplexitybot/1.0',
            Crawler::PerplexityBot,
        ];
    }

    /**
     * @return iterable<string, array{Crawler}>
     */
    public static function supportedCrawlers(): iterable
    {
        yield 'PerplexityBot' => [
            Crawler::PerplexityBot,
        ];

        yield 'Perplexity-User' => [
            Crawler::PerplexityUser,
        ];
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
