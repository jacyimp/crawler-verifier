<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerVerifier;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidConfigurationException::class)]
final class CrawlerVerifierConfigurationTest extends TestCase
{
    #[Test]
    public function itAcceptsAValidCacheKeyPrefix(): void
    {
        $verifier = new CrawlerVerifier(
            cacheKeyPrefix: 'my_app.crawlers',
        );

        self::assertSame(
            Crawler::GPTBot,
            $verifier->identify('GPTBot/1.1'),
        );
    }

    #[Test]
    public function itRejectsAnEmptyCacheKeyPrefix(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifier(
            cacheKeyPrefix: '',
        );
    }

    #[Test]
    public function itRejectsAnInvalidCacheKeyPrefix(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifier(
            cacheKeyPrefix: 'crawler/verifier',
        );
    }

    #[Test]
    public function itRejectsANegativeDnsCacheTtl(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifier(
            dnsCacheTtlSeconds: -1,
        );
    }

    #[Test]
    public function itRejectsANegativeDnsCacheNegativeTtl(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifier(
            dnsNegativeCacheTtlSeconds: -1,
        );
    }
}
