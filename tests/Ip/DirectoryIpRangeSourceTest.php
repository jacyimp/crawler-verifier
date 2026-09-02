<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Ip;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Ip\DirectoryIpRangeSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectoryIpRangeSource::class)]
final class DirectoryIpRangeSourceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir()
            . '/crawler-verifier-'
            . bin2hex(random_bytes(8));

        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    #[Test]
    public function itReadsCrawlerRangesFromTheDirectory(): void
    {
        file_put_contents(
            $this->directory . '/gptbot.json',
            <<<JSON
            {
                "prefixes": [
                    {
                        "ipv4Prefix": "192.0.2.0/24"
                    }
                ]
            }
            JSON,
        );

        $source = new DirectoryIpRangeSource($this->directory);

        self::assertSame(
            ['192.0.2.0/24'],
            $source->rangesFor(Crawler::GPTBot),
        );
    }

    #[Test]
    public function itReturnsNullWhenNoRangesExistForTheCrawler(): void
    {
        $source = new DirectoryIpRangeSource($this->directory);

        self::assertNull(
            $source->rangesFor(Crawler::Googlebot),
        );
    }
}
