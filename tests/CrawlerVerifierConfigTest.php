<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\CrawlerVerifierConfig;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidConfigurationException::class)]
final class CrawlerVerifierConfigTest extends TestCase
{
    #[Test]
    public function itAcceptsAValidCacheKeyPrefix(): void
    {
        $config = new CrawlerVerifierConfig(
            cacheKeyPrefix: 'my_app.crawlers',
        );

        self::assertSame(
            'my_app.crawlers',
            $config->cacheKeyPrefix,
        );
    }

    #[Test]
    public function itRejectsAnEmptyCacheKeyPrefix(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifierConfig(
            cacheKeyPrefix: '',
        );
    }

    #[Test]
    public function itRejectsAnInvalidCacheKeyPrefix(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifierConfig(
            cacheKeyPrefix: 'crawler/verifier',
        );
    }

    #[Test]
    public function itRejectsANegativeDnsCacheTtl(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifierConfig(
            dnsCacheTtlSeconds: -1,
        );
    }

    #[Test]
    public function itRejectsANegativeDnsCacheNegativeTtl(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CrawlerVerifierConfig(
            dnsNegativeCacheTtlSeconds: -1,
        );
    }
}
