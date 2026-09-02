<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Ip;

use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;
use JacyImp\CrawlerVerifier\Ip\NativeIpRangeFetcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NativeIpRangeFetcher::class)]
#[UsesClass(IpRangeSourceException::class)]
final class NativeIpRangeFetcherTest extends TestCase
{
    #[Test]
    public function itRejectsAnInvalidUrlWithAPackageException(): void
    {
        $this->expectException(
            IpRangeSourceException::class,
        );

        (new NativeIpRangeFetcher())->fetch(
            'definitely-not-a-url',
        );
    }

    #[Test]
    #[DataProvider('insecureUrls')]
    public function itRejectsNonHttpsUrls(string $url): void
    {
        $this->expectException(
            IpRangeSourceException::class,
        );
        $this->expectExceptionMessage(
            'IP range URL must use HTTPS',
        );

        (new NativeIpRangeFetcher())->fetch(
            $url,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function insecureUrls(): iterable
    {
        yield 'HTTP' => [
            'http://example.com/ranges.json',
        ];

        yield 'FTP' => [
            'ftp://example.com/ranges.json',
        ];
    }
}
