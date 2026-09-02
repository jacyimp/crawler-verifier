<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\VerificationMethod;

final class BingProvider extends IpRangeCrawlerProvider
{
    private const DNS_SUFFIXES = [
        'search.msn.com',
    ];

    public function __construct(
        IpRangeSource $rangeSource,
        IpRangeMatcher $rangeMatcher = new IpRangeMatcher(),
        private readonly ForwardConfirmedReverseDnsVerifier $dnsVerifier = new ForwardConfirmedReverseDnsVerifier(),
    ) {
        parent::__construct(
            $rangeSource,
            $rangeMatcher,
        );
    }

    public function verify(
        Crawler $crawler,
        string $ip,
    ): ?VerificationMethod {
        if (!$this->supports($crawler)) {
            return null;
        }

        if ($this->verifyIpRange($crawler, $ip)) {
            return VerificationMethod::IpRange;
        }

        if (
            $this->dnsVerifier->verify(
                $ip,
                self::DNS_SUFFIXES,
            )
        ) {
            return VerificationMethod::ForwardConfirmedReverseDns;
        }

        return null;
    }

    protected function userAgentTokens(): array
    {
        return [
            'bingbot' => Crawler::Bingbot,
            'msnbot' => Crawler::Bingbot,
        ];
    }
}
