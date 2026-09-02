<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Provider\PinterestProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PinterestProvider::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
final class PinterestProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesPinterestBot(
        string $userAgent,
    ): void {
        self::assertSame(
            Crawler::PinterestBot,
            $this->provider()->identify($userAgent),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialPinterestBotToken(): void
    {
        self::assertNull(
            $this->provider()->identify(
                'DefinitelyNotPinterestbot/1.0',
            ),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAnUnknownUserAgent(): void
    {
        self::assertNull(
            $this->provider()->identify(
                'Mozilla/5.0 Firefox/142.0',
            ),
        );
    }

    #[Test]
    public function itSupportsPinterestBot(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::PinterestBot,
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
    public function itVerifiesPinterestBotUsingPinterestDotComDns(): void
    {
        $provider = $this->provider(
            reverse: 'crawler.pinterest.com',
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
    public function itVerifiesPinterestBotUsingPinterestCrawlerDotComDns(): void
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
    public function itRejectsPinterestBotWithAnInvalidDnsSuffix(): void
    {
        $provider = $this->provider(
            reverse: 'pinterest.com.attacker.example',
            forward: [
                '192.0.2.42',
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
    public function itRejectsPinterestBotWhenForwardDnsDoesNotMatch(): void
    {
        $provider = $this->provider(
            reverse: 'crawler.pinterestcrawler.com',
            forward: [
                '203.0.113.42',
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
    public function itRejectsPinterestBotWhenReverseDnsFails(): void
    {
        $provider = $this->provider(
            reverse: null,
        );

        self::assertNull(
            $provider->verify(
                Crawler::PinterestBot,
                '192.0.2.42',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = $this->provider(
            reverse: 'crawler.pinterest.com',
            forward: [
                '192.0.2.42',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '192.0.2.42',
            ),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function userAgents(): iterable
    {
        yield 'Pinterestbot' => [
            'Mozilla/5.0 (compatible; Pinterestbot/1.0; +https://www.pinterest.com/bot.html)',
        ];

        yield 'Pinterest mobile crawler' => [
            'Mozilla/5.0 (Linux; Android 6.0.1) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) '
            . 'Chrome/41.0.2272.96 Mobile Safari/537.36 '
            . '(compatible; Pinterestbot/1.0; +https://www.pinterest.com/bot.html)',
        ];

        yield 'legacy Pinterest' => [
            'Pinterest/0.2 (+https://www.pinterest.com/bot.html)',
        ];

        yield 'case insensitive' => [
            'pinterestbot/1.0',
        ];
    }

    /**
     * @param list<string> $forward
     */
    private function provider(
        ?string $reverse = null,
        array $forward = [],
    ): PinterestProvider {
        return new PinterestProvider(
            new ForwardConfirmedReverseDnsVerifier(
                $this->resolver(
                    reverse: $reverse,
                    forward: $forward,
                ),
            ),
        );
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
