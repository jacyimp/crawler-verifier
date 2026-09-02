<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Dns\NativeDnsResolver;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;

final class CrawlerProviderRegistry
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

    public static function defaults(
        IpRangeSource $rangeSource,
        ?DnsResolver $dnsResolver = null,
    ): self {
        $dnsVerifier = new ForwardConfirmedReverseDnsVerifier(
            $dnsResolver ?? new NativeDnsResolver(),
        );

        return new self([
            new OpenAiProvider($rangeSource),
            new GoogleProvider(
                rangeSource: $rangeSource,
                dnsVerifier: $dnsVerifier,
            ),
            new BingProvider(
                rangeSource: $rangeSource,
                dnsVerifier: $dnsVerifier,
            ),
            new AppleProvider(
                rangeSource: $rangeSource,
                dnsVerifier: $dnsVerifier,
            ),
            new DuckDuckGoProvider($rangeSource),
            new PinterestProvider($dnsVerifier),
            new BaiduProvider($dnsVerifier),
            new PerplexityProvider($rangeSource),
        ]);
    }

    /**
     * @return list<CrawlerProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
