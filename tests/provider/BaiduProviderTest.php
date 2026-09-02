<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Provider\BaiduProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaiduProvider::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
final class BaiduProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('userAgents')]
    public function itIdentifiesBaiduSpider(
        string $userAgent,
    ): void {
        self::assertSame(
            Crawler::BaiduSpider,
            $this->provider()->identify($userAgent),
        );
    }

    #[Test]
    public function itDoesNotIdentifyAPartialBaiduSpiderToken(): void
    {
        self::assertNull(
            $this->provider()->identify(
                'DefinitelyNotBaiduspider/2.0',
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
    public function itSupportsBaiduSpider(): void
    {
        self::assertTrue(
            $this->provider()->supports(
                Crawler::BaiduSpider,
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
    public function itVerifiesBaiduSpiderUsingBaiduDotComDns(): void
    {
        $provider = $this->provider(
            reverse: 'baiduspider-111-206-198-69.crawl.baidu.com',
            forward: [
                '111.206.198.69',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::BaiduSpider,
                '111.206.198.69',
            ),
        );
    }

    #[Test]
    public function itVerifiesBaiduSpiderUsingBaiduDotJpDns(): void
    {
        $provider = $this->provider(
            reverse: 'crawler.baidu.jp',
            forward: [
                '192.0.2.42',
            ],
        );

        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $provider->verify(
                Crawler::BaiduSpider,
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
                '111.206.198.69',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::BaiduSpider,
                '111.206.198.69',
            ),
        );
    }

    #[Test]
    public function itRejectsWhenForwardDnsDoesNotMatchTheOriginalIp(): void
    {
        $provider = $this->provider(
            reverse: 'baiduspider-111-206-198-69.crawl.baidu.com',
            forward: [
                '203.0.113.42',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::BaiduSpider,
                '111.206.198.69',
            ),
        );
    }

    #[Test]
    public function itRejectsWhenReverseDnsFails(): void
    {
        $provider = $this->provider(
            reverse: null,
        );

        self::assertNull(
            $provider->verify(
                Crawler::BaiduSpider,
                '111.206.198.69',
            ),
        );
    }

    #[Test]
    public function itDoesNotVerifyAnUnsupportedCrawler(): void
    {
        $provider = $this->provider(
            reverse: 'baiduspider-111-206-198-69.crawl.baidu.com',
            forward: [
                '111.206.198.69',
            ],
        );

        self::assertNull(
            $provider->verify(
                Crawler::Googlebot,
                '111.206.198.69',
            ),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function userAgents(): iterable
    {
        yield 'Baiduspider' => [
            'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
        ];

        yield 'Baiduspider render' => [
            'Mozilla/5.0 (compatible; Baiduspider-render/2.0; +http://www.baidu.com/search/spider.html)',
        ];

        yield 'case insensitive' => [
            'baiduspider/2.0',
        ];
    }

    /**
     * @param list<string> $forward
     */
    private function provider(
        ?string $reverse = null,
        array $forward = [],
    ): BaiduProvider {
        return new BaiduProvider(
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
