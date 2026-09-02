<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

use JacyImp\CrawlerVerifier\Dns\CachingDnsResolver;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\NativeDnsResolver;
use JacyImp\CrawlerVerifier\Ip\DirectoryIpRangeSource;
use JacyImp\CrawlerVerifier\Ip\FallbackIpRangeSource;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\Ip\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\Provider\CrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\CrawlerProviderRegistry;

final class CrawlerVerifier
{
    /**
     * @var list<CrawlerProvider>
     */
    private array $providers;

    /**
     * @param iterable<CrawlerProvider> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = [...$providers];
    }

    public static function create(
        ?CrawlerVerifierConfig $config = null,
    ): self {
        $config ??= new CrawlerVerifierConfig();

        return new self(
            CrawlerProviderRegistry::defaults(
                rangeSource: self::createRangeSource(
                    $config,
                ),
                dnsResolver: self::createDnsResolver(
                    $config,
                ),
            )->all(),
        );
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

    private static function createRangeSource(
        CrawlerVerifierConfig $config,
    ): IpRangeSource {
        $sources = [];

        foreach ($config->localRangeDirectories as $directory) {
            $sources[] = new DirectoryIpRangeSource(
                $directory,
            );
        }

        if ($config->cache !== null) {
            $sources[] = new PsrCacheIpRangeSource(
                cache: $config->cache,
                cacheKeyPrefix: $config->cacheKeyPrefix,
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
        CrawlerVerifierConfig $config,
    ): DnsResolver {
        $resolver = new NativeDnsResolver();

        if ($config->cache === null) {
            return $resolver;
        }

        return new CachingDnsResolver(
            resolver: $resolver,
            cache: $config->cache,
            positiveTtlSeconds: $config->dnsCacheTtlSeconds,
            negativeTtlSeconds: $config->dnsNegativeCacheTtlSeconds,
            cacheKeyPrefix: $config->cacheKeyPrefix,
        );
    }
}
