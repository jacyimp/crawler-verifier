<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\CrawlerVerifier;
use JacyImp\CrawlerVerifier\Provider\CrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;
use JacyImp\CrawlerVerifier\VerificationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrawlerVerifier::class)]
#[UsesClass(VerificationResult::class)]
final class CrawlerVerifierTest extends TestCase
{
    #[Test]
    public function itIdentifiesASupportedCrawlerFromItsUserAgent(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::GPTBot,
                userAgentFragment: 'GPTBot',
            ),
        ]);

        self::assertSame(
            Crawler::GPTBot,
            $verifier->identify(
                'Mozilla/5.0; compatible; GPTBot/1.1',
            ),
        );
    }

    #[Test]
    public function itReturnsNullWhenTheUserAgentDoesNotMatchASupportedCrawler(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::GPTBot,
                userAgentFragment: 'GPTBot',
            ),
        ]);

        self::assertNull(
            $verifier->identify(
                'Mozilla/5.0 Firefox/142.0',
            ),
        );
    }

    #[Test]
    public function itVerifiesACrawlerThroughTheFullPipeline(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::GPTBot,
                userAgentFragment: 'GPTBot',
                verificationMethod: VerificationMethod::IpRange,
            ),
        ]);

        $result = $verifier->verify(
            userAgent: 'Mozilla/5.0; compatible; GPTBot/1.1',
            ip: '192.0.2.42',
        );

        self::assertTrue($result->verified);
        self::assertSame(
            Crawler::GPTBot,
            $result->crawler,
        );
        self::assertSame(
            VerificationMethod::IpRange,
            $result->method,
        );
    }

    #[Test]
    public function itReturnsAnUnverifiedResultWhenTheCrawlerCannotBeIdentified(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::GPTBot,
                userAgentFragment: 'GPTBot',
            ),
        ]);

        $result = $verifier->verify(
            userAgent: 'Mozilla/5.0 Firefox/142.0',
            ip: '192.0.2.42',
        );

        self::assertFalse($result->verified);
        self::assertNull($result->crawler);
        self::assertNull($result->method);
    }

    #[Test]
    public function itReturnsAnUnverifiedResultWhenCrawlerVerificationFails(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::GPTBot,
                userAgentFragment: 'GPTBot',
            ),
        ]);

        $result = $verifier->verify(
            userAgent: 'Mozilla/5.0; compatible; GPTBot/1.1',
            ip: '192.0.2.42',
        );

        self::assertFalse($result->verified);
        self::assertSame(
            Crawler::GPTBot,
            $result->crawler,
        );
        self::assertNull($result->method);
    }

    #[Test]
    public function itCanVerifyAKnownCrawlerWithoutAUserAgent(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::Googlebot,
                userAgentFragment: 'Googlebot',
                verificationMethod: VerificationMethod::IpRange,
            ),
        ]);

        $result = $verifier->verifyCrawler(
            Crawler::Googlebot,
            '192.0.2.42',
        );

        self::assertTrue($result->verified);
        self::assertSame(
            Crawler::Googlebot,
            $result->crawler,
        );
        self::assertSame(
            VerificationMethod::IpRange,
            $result->method,
        );
    }

    #[Test]
    public function itTriesTheNextSupportingProviderWhenTheFirstVerificationFails(): void
    {
        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: Crawler::Googlebot,
                userAgentFragment: 'Googlebot',
            ),
            $this->provider(
                crawler: Crawler::Googlebot,
                userAgentFragment: 'Googlebot',
                verificationMethod: VerificationMethod::ForwardConfirmedReverseDns,
            ),
        ]);

        $result = $verifier->verifyCrawler(
            Crawler::Googlebot,
            '192.0.2.42',
        );

        self::assertTrue($result->verified);
        self::assertSame(
            Crawler::Googlebot,
            $result->crawler,
        );
        self::assertSame(
            VerificationMethod::ForwardConfirmedReverseDns,
            $result->method,
        );
    }

    #[Test]
    public function itCanIdentifyAndVerifyACustomCrawlerIdentity(): void
    {
        $crawler = new class implements CrawlerIdentity {
            public function id(): string
            {
                return 'my-company-bot';
            }
        };

        $verifier = new CrawlerVerifier([
            $this->provider(
                crawler: $crawler,
                userAgentFragment: 'MyCompanyBot',
                verificationMethod: VerificationMethod::IpRange,
            ),
        ]);

        self::assertSame(
            $crawler,
            $verifier->identify(
                'Mozilla/5.0; compatible; MyCompanyBot/1.0',
            ),
        );

        $result = $verifier->verify(
            userAgent: 'Mozilla/5.0; compatible; MyCompanyBot/1.0',
            ip: '192.0.2.42',
        );

        self::assertTrue($result->verified);
        self::assertSame($crawler, $result->crawler);
        self::assertSame(
            'my-company-bot',
            $result->crawler?->id(),
        );
        self::assertSame(
            VerificationMethod::IpRange,
            $result->method,
        );
    }

    #[Test]
    public function itReturnsAnUnverifiedResultWhenNoProviderSupportsTheCrawler(): void
    {
        $verifier = new CrawlerVerifier([]);

        $result = $verifier->verifyCrawler(
            Crawler::Bingbot,
            '192.0.2.42',
        );

        self::assertFalse($result->verified);
        self::assertSame(
            Crawler::Bingbot,
            $result->crawler,
        );
        self::assertNull($result->method);
    }

    private function provider(
        CrawlerIdentity $crawler,
        string $userAgentFragment,
        ?VerificationMethod $verificationMethod = null,
    ): CrawlerProvider {
        return new class(
            $crawler,
            $userAgentFragment,
            $verificationMethod,
        ) implements CrawlerProvider {
            public function __construct(
                private readonly CrawlerIdentity $crawler,
                private readonly string $userAgentFragment,
                private readonly ?VerificationMethod $verificationMethod,
            ) {
            }

            public function identify(
                string $userAgent,
            ): ?CrawlerIdentity {
                return str_contains(
                    $userAgent,
                    $this->userAgentFragment,
                )
                    ? $this->crawler
                    : null;
            }

            public function supports(
                CrawlerIdentity $crawler,
            ): bool {
                return $crawler === $this->crawler;
            }

            public function verify(
                CrawlerIdentity $crawler,
                string $ip,
            ): ?VerificationMethod {
                return $this->verificationMethod;
            }
        };
    }
}
