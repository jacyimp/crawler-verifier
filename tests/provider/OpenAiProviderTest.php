<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\OpenAiProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAiProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
final class OpenAiProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesOpenAiCrawlers(
        string $userAgent,
        Crawler $crawler,
    ): void {
        $provider = new OpenAiProvider(
            $this->rangeSource(null),
        );

        self::assertSame(
            $crawler,
            $provider->identify($userAgent),
        );
    }

    #[Test]
    public function itDoesNotIdentifyPartialTokenMatches(): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource(null),
        );

        self::assertNull(
            $provider->identify('DefinitelyNotGPTBotCrawler/1.0'),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAnUnknownUserAgent(): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource(null),
        );

        self::assertNull(
            $provider->identify('Mozilla/5.0 Firefox/142.0'),
        );
    }

    #[Test]
    #[DataProvider('supportedCrawlers')]
    public function itSupportsOpenAiCrawlers(Crawler $crawler): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource(null),
        );

        self::assertTrue($provider->supports($crawler));
    }

    #[Test]
    public function itDoesNotSupportOtherCrawlers(): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource(null),
        );

        self::assertFalse(
            $provider->supports(Crawler::Googlebot),
        );
    }

    #[Test]
    public function itVerifiesAnIpInsideTheCrawlerRange(): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource([
                '192.0.2.0/24',
            ]),
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
    public function itRejectsAnIpOutsideTheCrawlerRange(): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource([
                '192.0.2.0/24',
            ]),
        );

        self::assertNull(
            $provider->verify(
                Crawler::GPTBot,
                '203.0.113.42',
            ),
        );
    }

    #[Test]
    public function itRejectsVerificationWhenNoRangesAreAvailable(): void
    {
        $provider = new OpenAiProvider(
            $this->rangeSource(null),
        );

        self::assertNull(
            $provider->verify(
                Crawler::GPTBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = new OpenAiProvider(
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
        yield 'GPTBot' => [
            'Mozilla/5.0; compatible; GPTBot/1.1; +https://openai.com/gptbot',
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
    }

    /**
     * @return iterable<string, array{Crawler}>
     */
    public static function supportedCrawlers(): iterable
    {
        yield 'GPTBot' => [Crawler::GPTBot];
        yield 'OAI-SearchBot' => [Crawler::OaiSearchBot];
        yield 'OAI-AdsBot' => [Crawler::OaiAdsBot];
    }

    /**
     * @param list<string>|null $ranges
     */
    private function rangeSource(?array $ranges): IpRangeSource
    {
        return new class($ranges) implements IpRangeSource {
            /**
             * @param list<string>|null $ranges
             */
            public function __construct(
                private readonly ?array $ranges,
            ) {
            }

            public function rangesFor(Crawler $crawler): ?array
            {
                return $this->ranges;
            }
        };
    }
}
