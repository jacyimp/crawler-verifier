<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Dns;

use JacyImp\CrawlerVerifier\Dns\NativeDnsResolver;
use JacyImp\CrawlerVerifier\Tests\Support\NativeFunctions;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeDnsResolverTest extends TestCase
{
    #[After]
    public function resetNativeFunctions(): void
    {
        NativeFunctions::reset();
    }

    #[Test]
    public function itReturnsANormalizedReverseDnsResult(): void
    {
        NativeFunctions::$reverse = static fn (string $ip): string => 'Crawler.Example.';

        self::assertSame('Crawler.Example', (new NativeDnsResolver())->reverse('192.0.2.1'));
    }

    #[Test]
    public function itReturnsNullForFailedAndUnchangedReverseLookups(): void
    {
        NativeFunctions::$reverse = static fn (string $ip): false => false;
        self::assertNull((new NativeDnsResolver())->reverse('192.0.2.1'));

        NativeFunctions::$reverse = static fn (string $ip): string => $ip;
        self::assertNull((new NativeDnsResolver())->reverse('192.0.2.1'));
    }

    #[Test]
    public function itReturnsUniqueIpv4AndIpv6ForwardResults(): void
    {
        NativeFunctions::$forward = static fn (string $hostname, int $type): array => [
            ['ip' => '192.0.2.1'],
            ['ipv6' => '2001:db8::1'],
            ['ip' => '192.0.2.1'],
            ['ip' => 123, 'ipv6' => false],
        ];

        self::assertSame(
            ['192.0.2.1', '2001:db8::1'],
            (new NativeDnsResolver())->forward('crawler.example'),
        );
    }

    #[Test]
    public function itReturnsNoAddressesWhenForwardLookupFails(): void
    {
        NativeFunctions::$forward = static fn (string $hostname, int $type): false => false;

        self::assertSame([], (new NativeDnsResolver())->forward('crawler.example'));
    }
}
