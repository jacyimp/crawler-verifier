<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerVerifier;
use JacyImp\CrawlerVerifier\CrawlerVerifierConfig;
use JacyImp\CrawlerVerifier\Dns\CachingDnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\DirectoryIpRangeSource;
use JacyImp\CrawlerVerifier\Ip\FallbackIpRangeSource;
use JacyImp\CrawlerVerifier\Ip\IpRangeFeed;
use JacyImp\CrawlerVerifier\Ip\IpRangeFetcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeUpdater;
use JacyImp\CrawlerVerifier\Ip\IpRangeUpdateResult;
use JacyImp\CrawlerVerifier\Ip\JsonIpRangeParser;
use JacyImp\CrawlerVerifier\Ip\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\Provider\AppleProvider;
use JacyImp\CrawlerVerifier\Provider\BaiduProvider;
use JacyImp\CrawlerVerifier\Provider\BingProvider;
use JacyImp\CrawlerVerifier\Provider\CrawlerProviderRegistry;
use JacyImp\CrawlerVerifier\Provider\DuckDuckGoProvider;
use JacyImp\CrawlerVerifier\Provider\GoogleProvider;
use JacyImp\CrawlerVerifier\Provider\IpRangeCrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\OpenAiProvider;
use JacyImp\CrawlerVerifier\Provider\PerplexityProvider;
use JacyImp\CrawlerVerifier\Provider\PinterestProvider;
use JacyImp\CrawlerVerifier\Tests\Support\ArrayCache;
use JacyImp\CrawlerVerifier\VerificationMethod;
use JacyImp\CrawlerVerifier\VerificationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrawlerVerifier::class)]
#[UsesClass(CrawlerVerifierConfig::class)]
#[UsesClass(CrawlerProviderRegistry::class)]
#[UsesClass(VerificationResult::class)]
#[UsesClass(IpRangeCrawlerProvider::class)]
#[UsesClass(OpenAiProvider::class)]
#[UsesClass(GoogleProvider::class)]
#[UsesClass(BingProvider::class)]
#[UsesClass(AppleProvider::class)]
#[UsesClass(DuckDuckGoProvider::class)]
#[UsesClass(PinterestProvider::class)]
#[UsesClass(BaiduProvider::class)]
#[UsesClass(PerplexityProvider::class)]
#[UsesClass(ForwardConfirmedReverseDnsVerifier::class)]
#[UsesClass(CachingDnsResolver::class)]
#[UsesClass(DirectoryIpRangeSource::class)]
#[UsesClass(FallbackIpRangeSource::class)]
#[UsesClass(PsrCacheIpRangeSource::class)]
#[UsesClass(IpRangeMatcher::class)]
#[UsesClass(JsonIpRangeParser::class)]
#[UsesClass(IpRangeUpdater::class)]
#[UsesClass(IpRangeFeed::class)]
#[UsesClass(IpRangeUpdateResult::class)]
final class CrawlerVerifierFactoryTest extends TestCase
{
    #[Test]
    public function itUsesCachedRanges(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
            ),
            [
                'ranges' => ['192.0.2.0/24'],
                'refreshed_at' => time(),
            ],
        );

        $verifier = CrawlerVerifier::create(
            new CrawlerVerifierConfig(
                cache: $cache,
            ),
        );

        $result = $verifier->verify(
            userAgent: 'GPTBot/1.1',
            ip: '192.0.2.42',
        );

        self::assertTrue($result->verified);
        self::assertSame(
            VerificationMethod::IpRange,
            $result->method,
        );
    }

    #[Test]
    public function itUsesTheConfiguredCacheKeyPrefix(): void
    {
        $cache = new ArrayCache();

        $cache->set(
            PsrCacheIpRangeSource::key(
                Crawler::GPTBot,
                'my_app',
            ),
            [
                'ranges' => ['192.0.2.0/24'],
                'refreshed_at' => time(),
            ],
        );

        $verifier = CrawlerVerifier::create(
            new CrawlerVerifierConfig(
                cache: $cache,
                cacheKeyPrefix: 'my_app',
            ),
        );

        self::assertTrue(
            $verifier->verify(
                userAgent: 'GPTBot/1.1',
                ip: '192.0.2.42',
            )->verified,
        );
    }

    #[Test]
    public function itPrefersSuppliedLocalRangesOverCachedRanges(): void
    {
        $localDirectory = sys_get_temp_dir()
            . '/crawler-verifier-local-'
            . bin2hex(random_bytes(8));

        mkdir($localDirectory);

        $cache = new ArrayCache();

        try {
            file_put_contents(
                $localDirectory . '/gptbot.json',
                <<<'JSON'
                {
                    "prefixes": [
                        {
                            "ipv4Prefix": "192.0.2.0/24"
                        }
                    ]
                }
                JSON,
            );

            $cache->set(
                PsrCacheIpRangeSource::key(
                    Crawler::GPTBot,
                ),
                [
                    'ranges' => ['203.0.113.0/24'],
                    'refreshed_at' => time(),
                ],
            );

            $verifier = CrawlerVerifier::create(
                new CrawlerVerifierConfig(
                    cache: $cache,
                    localRangeDirectories: [
                        $localDirectory,
                    ],
                ),
            );

            self::assertTrue(
                $verifier->verify(
                    userAgent: 'GPTBot/1.1',
                    ip: '192.0.2.42',
                )->verified,
            );

            self::assertFalse(
                $verifier->verify(
                    userAgent: 'GPTBot/1.1',
                    ip: '203.0.113.42',
                )->verified,
            );
        } finally {
            @unlink(
                $localDirectory . '/gptbot.json',
            );

            @rmdir($localDirectory);
        }
    }

    #[Test]
    public function itUsesRefreshedRangesForVerification(): void
    {
        $cache = new ArrayCache();

        $updater = new IpRangeUpdater(
            cache: $cache,
            feeds: [
                new IpRangeFeed(
                    Crawler::GPTBot,
                    'https://example.com/gptbot.json',
                ),
            ],
            fetcher: $this->fetcher([
                'https://example.com/gptbot.json' => <<<'JSON'
                {
                    "prefixes": [
                        {
                            "ipv4Prefix": "192.0.2.0/24"
                        }
                    ]
                }
                JSON,
            ]),
        );

        self::assertTrue(
            $updater->refresh()->successful(),
        );

        $verifier = CrawlerVerifier::create(
            new CrawlerVerifierConfig(
                cache: $cache,
            ),
        );

        $result = $verifier->verify(
            userAgent: 'GPTBot/1.1',
            ip: '192.0.2.42',
        );

        self::assertTrue($result->verified);
        self::assertSame(
            VerificationMethod::IpRange,
            $result->method,
        );
    }

    /**
     * @param array<string, string> $responses
     */
    private function fetcher(
        array $responses,
    ): IpRangeFetcher {
        return new class($responses) implements IpRangeFetcher {
            /**
             * @param array<string, string> $responses
             */
            public function __construct(
                private readonly array $responses,
            ) {
            }

            public function fetch(
                string $url,
            ): string {
                return $this->responses[$url];
            }
        };
    }
}
