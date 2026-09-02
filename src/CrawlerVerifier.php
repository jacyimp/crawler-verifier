<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Dns\CachingDnsResolver;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Dns\NativeDnsResolver;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\IpRange\Source\DirectoryIpRangeSource;
use JacyImp\CrawlerVerifier\IpRange\Source\FallbackIpRangeSource;
use JacyImp\CrawlerVerifier\IpRange\Source\IpRangeSource;
use JacyImp\CrawlerVerifier\IpRange\Source\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\Provider\BuiltInCrawlerProvider;
use Psr\SimpleCache\CacheInterface;

final class CrawlerVerifier
{
    /**
     * @var list<CrawlerProvider>
     */
    private array $providers;

    /**
     * @param iterable<CrawlerProvider> $additionalProviders
     * @param list<string> $localRangeDirectories
     */
    public function __construct(
        ?CacheInterface $cache = null,
        iterable $additionalProviders = [],
        array $localRangeDirectories = [],
        string $cacheKeyPrefix = 'crawler_verifier',
        int $dnsCacheTtlSeconds = 3600,
        int $dnsNegativeCacheTtlSeconds = 300,
    ) {
        self::validateConfiguration(
            cacheKeyPrefix: $cacheKeyPrefix,
            dnsCacheTtlSeconds: $dnsCacheTtlSeconds,
            dnsNegativeCacheTtlSeconds: $dnsNegativeCacheTtlSeconds,
        );

        $builtInProvider =
            new BuiltInCrawlerProvider(
                catalog: BuiltInCrawlerCatalog::defaults(),
                rangeSource: self::createRangeSource(
                    cache: $cache,
                    localRangeDirectories: $localRangeDirectories,
                    cacheKeyPrefix: $cacheKeyPrefix,
                ),
                dnsVerifier: new ForwardConfirmedReverseDnsVerifier(
                    self::createDnsResolver(
                        cache: $cache,
                        cacheKeyPrefix: $cacheKeyPrefix,
                        dnsCacheTtlSeconds: $dnsCacheTtlSeconds,
                        dnsNegativeCacheTtlSeconds: $dnsNegativeCacheTtlSeconds,
                    ),
                ),
            );

        $this->providers = array_values([
            $builtInProvider,
            ...$additionalProviders,
        ]);
    }

    public function verify(
        string $userAgent,
        string $ip,
    ): VerificationResult {
        $crawler = $this->identify(
            $userAgent,
        );

        if ($crawler === null) {
            return VerificationResult::unverified();
        }

        return $this->verifyCrawler(
            $crawler,
            $ip,
        );
    }

    public function identify(
        string $userAgent,
    ): ?CrawlerIdentity {
        foreach ($this->providers as $provider) {
            $crawler = $provider->identify(
                $userAgent,
            );

            if ($crawler !== null) {
                return $crawler;
            }
        }

        return null;
    }

    public function verifyCrawler(
        CrawlerIdentity $crawler,
        string $ip,
    ): VerificationResult {
        foreach ($this->providers as $provider) {
            if (!$provider->supports($crawler)) {
                continue;
            }

            $method = $provider->verify(
                $crawler,
                $ip,
            );

            if ($method !== null) {
                return VerificationResult::verified(
                    crawler: $crawler,
                    method: $method,
                );
            }
        }

        return VerificationResult::unverified(
            $crawler,
        );
    }

    /**
     * @param list<string> $localRangeDirectories
     */
    private static function createRangeSource(
        ?CacheInterface $cache,
        array $localRangeDirectories,
        string $cacheKeyPrefix,
    ): IpRangeSource {
        $sources = [];

        foreach ($localRangeDirectories as $directory) {
            $sources[] = new DirectoryIpRangeSource(
                $directory,
            );
        }

        if ($cache !== null) {
            $sources[] = new PsrCacheIpRangeSource(
                cache: $cache,
                cacheKeyPrefix: $cacheKeyPrefix,
            );
        }

        $sources[] = new DirectoryIpRangeSource(
            dirname(__DIR__)
            . '/resources/ip-ranges',
        );

        return new FallbackIpRangeSource(
            $sources,
        );
    }

    private static function createDnsResolver(
        ?CacheInterface $cache,
        string $cacheKeyPrefix,
        int $dnsCacheTtlSeconds,
        int $dnsNegativeCacheTtlSeconds,
    ): DnsResolver {
        $resolver = new NativeDnsResolver();

        if ($cache === null) {
            return $resolver;
        }

        return new CachingDnsResolver(
            resolver: $resolver,
            cache: $cache,
            positiveTtlSeconds: $dnsCacheTtlSeconds,
            negativeTtlSeconds: $dnsNegativeCacheTtlSeconds,
            cacheKeyPrefix: $cacheKeyPrefix,
        );
    }

    private static function validateConfiguration(
        string $cacheKeyPrefix,
        int $dnsCacheTtlSeconds,
        int $dnsNegativeCacheTtlSeconds,
    ): void {
        if (
            $cacheKeyPrefix === ''
            || preg_match('/^[A-Za-z0-9_.]+$/', $cacheKeyPrefix) !== 1
        ) {
            throw InvalidConfigurationException::invalidCacheKeyPrefix();
        }

        if ($dnsCacheTtlSeconds < 0) {
            throw InvalidConfigurationException::negativeDnsCacheTtl();
        }

        if ($dnsNegativeCacheTtlSeconds < 0) {
            throw InvalidConfigurationException::negativeDnsNegativeCacheTtl();
        }
    }
}
