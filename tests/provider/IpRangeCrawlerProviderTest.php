<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IpRangeCrawlerProvider::class)]
#[UsesClass(IpRangeMatcher::class)]
final class IpRangeCrawlerProviderTest extends TestCase
{
    #[Test]
    public function itIdentifiesACrawlerFromItsUserAgentToken(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::GPTBot,
            $provider->identify(
                'Mozilla/5.0; compatible; GPTBot/1.1',
            ),
        );
    }

    #[Test]
    public function itMatchesUserAgentTokensCaseInsensitively(): void
    {
        $provider = $this->provider();

        self::assertSame(
            Crawler::GPTBot,
            $provider->identify(
                'Mozilla/5.0; compatible; gptbot/1.1',
            ),
        );
    }

    #[Test]
    public function itDoesNotMatchPartialUserAgentTokens(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'DefinitelyNotGPTBot/1.1',
            ),
        );
    }

    #[Test]
    public function itReturnsNullForAnUnknownUserAgent(): void
    {
        $provider = $this->provider();

        self::assertNull(
            $provider->identify(
                'Mozilla/5.0 Firefox/142.0',
            ),
        );
    }

    #[Test]
    public function itReportsWhetherACrawlerIsSupported(): void
    {
        $provider = $this->provider();

        self::assertTrue(
            $provider->supports(Crawler::GPTBot),
        );

        self::assertFalse(
            $provider->supports(Crawler::Googlebot),
        );
    }

    #[Test]
    public function itVerifiesAnIpInsideThePublishedRanges(): void
    {
        $provider = $this->provider([
            '192.0.2.0/24',
        ]);

        self::assertSame(
            VerificationMethod::IpRange,
            $provider->verify(
                Crawler::GPTBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itRejectsAnIpOutsideThePublishedRanges(): void
    {
        $provider = $this->provider([
            '192.0.2.0/24',
        ]);

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
        $provider = $this->provider(null);

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
        $provider = $this->provider([
            '192.0.2.0/24',
        ]);

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '192.0.2.42',
            ),
        );
    }

    /**
     * @param list<string>|null $ranges
     */
    private function provider(
        ?array $ranges = null,
    ): IpRangeCrawlerProvider {
        return new class(
            $this->rangeSource($ranges),
        ) extends IpRangeCrawlerProvider {
            protected function userAgentTokens(): array
            {
                return [
                    'GPTBot' => Crawler::GPTBot,
                ];
            }
        };
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
