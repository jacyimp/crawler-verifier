<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\VerificationMethod;

final readonly class BaiduProvider implements CrawlerProvider
{
    private const USER_AGENT_TOKENS = [
        'Baiduspider',
        'Baiduspider-render',
    ];

    private const DNS_SUFFIXES = [
        'baidu.com',
        'baidu.jp',
    ];

    public function __construct(
        private ForwardConfirmedReverseDnsVerifier $dnsVerifier = new ForwardConfirmedReverseDnsVerifier(),
    ) {
    }

    public function identify(string $userAgent): ?Crawler
    {
        foreach (self::USER_AGENT_TOKENS as $token) {
            if ($this->containsToken($userAgent, $token)) {
                return Crawler::BaiduSpider;
            }
        }

        return null;
    }

    public function supports(CrawlerIdentity $crawler): bool
    {
        return $crawler === Crawler::BaiduSpider;
    }

    public function verify(
        CrawlerIdentity $crawler,
        string $ip,
    ): ?VerificationMethod {
        if (!$this->supports($crawler)) {
            return null;
        }

        if (
            !$this->dnsVerifier->verify(
                $ip,
                self::DNS_SUFFIXES,
            )
        ) {
            return null;
        }

        return VerificationMethod::ForwardConfirmedReverseDns;
    }

    private function containsToken(
        string $userAgent,
        string $token,
    ): bool {
        return preg_match(
                sprintf(
                    '/(?:^|[\s;(])%s(?:\/[^\s;)]+)?(?=$|[\s;)])/i',
                    preg_quote($token, '/'),
                ),
                $userAgent,
            ) === 1;
    }
}
