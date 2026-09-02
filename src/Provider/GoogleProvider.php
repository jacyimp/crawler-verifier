<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\VerificationMethod;

final class GoogleProvider extends IpRangeCrawlerProvider
{
    private const DNS_SUFFIXES = [
        'googlebot.com',
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
        CrawlerIdentity $crawler,
        string $ip,
    ): ?VerificationMethod {
        if (!$crawler instanceof Crawler || !$this->supports($crawler)) {
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
            'Googlebot' => Crawler::Googlebot,
            'Googlebot-Image' => Crawler::Googlebot,
            'Googlebot-News' => Crawler::Googlebot,
            'Googlebot-Video' => Crawler::Googlebot,
        ];
    }
}
