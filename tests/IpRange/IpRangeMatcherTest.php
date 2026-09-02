<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange;

use JacyImp\CrawlerVerifier\IpRange\IpRangeMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IpRangeMatcherTest extends TestCase
{
    private IpRangeMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new IpRangeMatcher();
    }

    #[Test]
    #[DataProvider('matchingRanges')]
    public function itMatchesAnIpInsideARange(string $ip, string $range): void
    {
        self::assertTrue($this->matcher->contains($ip, $range));
    }

    #[Test]
    #[DataProvider('nonMatchingRanges')]
    public function itRejectsAnIpOutsideARange(string $ip, string $range): void
    {
        self::assertFalse($this->matcher->contains($ip, $range));
    }

    #[Test]
    public function itMatchesAgainstMultipleRanges(): void
    {
        self::assertTrue($this->matcher->matches(
            '20.171.206.42',
            [
                '192.0.2.0/24',
                '20.171.206.0/24',
                '203.0.113.0/24',
            ],
        ));
    }

    #[Test]
    public function itRejectsWhenNoneOfTheRangesMatch(): void
    {
        self::assertFalse($this->matcher->matches(
            '198.51.100.1',
            [
                '192.0.2.0/24',
                '203.0.113.0/24',
            ],
        ));
    }

    #[Test]
    public function itRejectsAnInvalidIpAddress(): void
    {
        self::assertFalse($this->matcher->contains(
            'not-an-ip',
            '192.0.2.0/24',
        ));
    }

    #[Test]
    public function itRejectsAnInvalidRange(): void
    {
        self::assertFalse($this->matcher->contains(
            '192.0.2.1',
            'not-a-range',
        ));
    }

    #[Test]
    public function itRejectsMalformedRangesWithoutTreatingThemAsWildcardPrefixes(): void
    {
        self::assertFalse($this->matcher->contains('0.0.0.0', '0.0.0.0/not-a-prefix'));
        self::assertFalse($this->matcher->contains('0.0.0.0', '0.0.0.0'));
    }

    #[Test]
    #[DataProvider('invalidPrefixLengths')]
    public function itRejectsAnInvalidPrefixLength(string $ip, string $range): void
    {
        self::assertFalse($this->matcher->contains($ip, $range));
    }

    #[Test]
    public function itRejectsDifferentIpAddressFamilies(): void
    {
        self::assertFalse($this->matcher->contains(
            '192.0.2.1',
            '2001:db8::/32',
        ));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function matchingRanges(): iterable
    {
        yield 'IPv4 /24' => [
            '192.0.2.42',
            '192.0.2.0/24',
        ];

        yield 'IPv4 first address' => [
            '192.0.2.0',
            '192.0.2.0/24',
        ];

        yield 'IPv4 last address' => [
            '192.0.2.255',
            '192.0.2.0/24',
        ];

        yield 'IPv4 exact address' => [
            '192.0.2.42',
            '192.0.2.42/32',
        ];

        yield 'IPv4 wildcard range' => [
            '203.0.113.42',
            '0.0.0.0/0',
        ];

        yield 'IPv6 /32' => [
            '2001:db8:1234::1',
            '2001:db8::/32',
        ];

        yield 'IPv6 exact address' => [
            '2001:db8::42',
            '2001:db8::42/128',
        ];

        yield 'IPv6 wildcard range' => [
            '2001:db8::1',
            '::/0',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonMatchingRanges(): iterable
    {
        yield 'IPv4 outside /24' => [
            '192.0.3.1',
            '192.0.2.0/24',
        ];

        yield 'IPv4 outside /28' => [
            '192.0.2.32',
            '192.0.2.0/28',
        ];

        yield 'IPv4 outside /1 differing in the highest bit' => [
            '128.0.0.0',
            '0.0.0.0/1',
        ];

        yield 'IPv4 outside /7 differing in a masked low bit' => [
            '2.0.0.0',
            '0.0.0.0/7',
        ];

        yield 'IPv4 exact mismatch' => [
            '192.0.2.43',
            '192.0.2.42/32',
        ];

        yield 'IPv6 outside range' => [
            '2001:db9::1',
            '2001:db8::/32',
        ];

        yield 'IPv6 exact mismatch' => [
            '2001:db8::43',
            '2001:db8::42/128',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPrefixLengths(): iterable
    {
        yield 'IPv4 prefix larger than 32 bits' => [
            '192.0.2.1',
            '192.0.2.0/33',
        ];

        yield 'IPv6 prefix larger than 128 bits' => [
            '2001:db8::1',
            '2001:db8::/129',
        ];
    }
}
