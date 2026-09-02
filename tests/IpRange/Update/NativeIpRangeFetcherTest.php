<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange\Update;

use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;
use JacyImp\CrawlerVerifier\IpRange\Update\NativeIpRangeFetcher;
use JacyImp\CrawlerVerifier\Tests\Support\NativeFunctions;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(IpRangeSourceException::class)]
final class NativeIpRangeFetcherTest extends TestCase
{
    #[After]
    public function resetNativeFunctions(): void
    {
        NativeFunctions::reset();
    }

    #[Test]
    public function itRejectsAnInvalidUrlWithAPackageException(): void
    {
        $this->expectException(
            IpRangeSourceException::class,
        );
        $this->expectExceptionMessage('Invalid IP range URL');

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

    #[Test]
    public function itReturnsFetchedHttpsContents(): void
    {
        NativeFunctions::$fetch = static function (string $url, bool $useIncludePath, mixed $context): string {
            self::assertSame('https://example.com/ranges.json', $url);
            self::assertFalse($useIncludePath);
            self::assertIsResource($context);
            self::assertSame([
                'http' => [
                    'timeout' => 10,
                    'follow_location' => 1,
                    'user_agent' => 'jacyimp/crawler-verifier',
                ],
            ], stream_context_get_options($context));

            return '{"prefixes":[]}';
        };

        self::assertSame(
            '{"prefixes":[]}',
            (new NativeIpRangeFetcher())->fetch('https://example.com/ranges.json'),
        );
    }

    #[Test]
    public function itAcceptsAnUppercaseHttpsScheme(): void
    {
        NativeFunctions::$fetch = static fn (): string => '{}';

        self::assertSame('{}', (new NativeIpRangeFetcher())->fetch('HTTPS://example.com/ranges.json'));
    }

    #[Test]
    public function itWrapsFailedHttpsFetches(): void
    {
        NativeFunctions::$fetch = static fn (): false => false;

        $this->expectException(IpRangeSourceException::class);
        $this->expectExceptionMessage('Unable to fetch IP ranges');

        (new NativeIpRangeFetcher())->fetch('https://example.com/ranges.json');
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
