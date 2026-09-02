<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Dns;

use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ForwardConfirmedReverseDnsVerifierTest extends TestCase
{
    #[Test]
    public function itVerifiesAForwardConfirmedReverseDnsLookup(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'crawl-66-249-66-1.googlebot.com',
                forward: [
                    '66.249.66.1',
                ],
            ),
        );

        self::assertTrue(
            $verifier->verify(
                '66.249.66.1',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itAcceptsATrailingDotInTheReverseHostname(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'crawl-66-249-66-1.googlebot.com.',
                forward: [
                    '66.249.66.1',
                ],
            ),
        );

        self::assertTrue(
            $verifier->verify(
                '66.249.66.1',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itMatchesHostnamesAndSuffixesCaseInsensitively(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier($this->resolver(
            reverse: 'CRAWL.GOOGLEBOT.COM',
            forward: ['66.249.66.1'],
        ));

        self::assertTrue($verifier->verify('66.249.66.1', ['GOOGLEBOT.COM']));
    }

    #[Test]
    public function itAcceptsDotsAroundAnAllowedSuffix(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier($this->resolver(
            reverse: 'crawl.googlebot.com',
            forward: ['66.249.66.1'],
        ));

        self::assertTrue($verifier->verify('66.249.66.1', ['.googlebot.com.']));
    }

    #[Test]
    public function itRejectsAHostnameOutsideTheAllowedSuffix(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'googlebot.com.evil.example',
                forward: [
                    '66.249.66.1',
                ],
            ),
        );

        self::assertFalse(
            $verifier->verify(
                '66.249.66.1',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itRejectsAHostnameThatOnlyEndsWithTheSameCharacters(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'fakegooglebot.com',
                forward: [
                    '66.249.66.1',
                ],
            ),
        );

        self::assertFalse(
            $verifier->verify(
                '66.249.66.1',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itRejectsWhenForwardDnsDoesNotContainTheOriginalIp(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'crawl-66-249-66-1.googlebot.com',
                forward: [
                    '203.0.113.1',
                ],
            ),
        );

        self::assertFalse(
            $verifier->verify(
                '66.249.66.1',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itRejectsWhenReverseDnsCannotBeResolved(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: null,
                forward: [],
            ),
        );

        self::assertFalse(
            $verifier->verify(
                '66.249.66.1',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itRejectsAnInvalidIpAddress(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'crawl.googlebot.com',
                forward: [
                    '66.249.66.1',
                ],
            ),
        );

        self::assertFalse(
            $verifier->verify(
                'not-an-ip',
                ['googlebot.com'],
            ),
        );
    }

    #[Test]
    public function itComparesIpv6AddressesByTheirBinaryRepresentation(): void
    {
        $verifier = new ForwardConfirmedReverseDnsVerifier(
            $this->resolver(
                reverse: 'crawl.googlebot.com',
                forward: [
                    '2001:0db8:0000:0000:0000:0000:0000:0001',
                ],
            ),
        );

        self::assertTrue(
            $verifier->verify(
                '2001:db8::1',
                ['googlebot.com'],
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
        return new class (
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

            /**
             * @return list<string>
             */
            public function forward(
                string $hostname,
            ): array {
                return $this->forward;
            }
        };
    }
}
