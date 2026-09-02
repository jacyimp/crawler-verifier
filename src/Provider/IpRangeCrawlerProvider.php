<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Ip\IpRangeMatcher;
use JacyImp\CrawlerVerifier\Ip\IpRangeSource;
use JacyImp\CrawlerVerifier\VerificationMethod;

abstract class IpRangeCrawlerProvider implements CrawlerProvider
{
    public function __construct(
        private readonly IpRangeSource $rangeSource,
        private readonly IpRangeMatcher $rangeMatcher = new IpRangeMatcher(),
    ) {
    }

    public function identify(string $userAgent): ?Crawler
    {
        foreach ($this->userAgentTokens() as $token => $crawler) {
            if ($this->containsToken($userAgent, $token)) {
                return $crawler;
            }
        }

        return null;
    }

    public function supports(Crawler $crawler): bool
    {
        return in_array(
            $crawler,
            $this->userAgentTokens(),
            true,
        );
    }

    public function verify(
        Crawler $crawler,
        string $ip,
    ): ?VerificationMethod {
        if (!$this->supports($crawler)) {
            return null;
        }

        return $this->verifyIpRange($crawler, $ip)
            ? VerificationMethod::IpRange
            : null;
    }

    /**
     * @return array<string, Crawler>
     */
    abstract protected function userAgentTokens(): array;

    protected function verifyIpRange(
        Crawler $crawler,
        string $ip,
    ): bool {
        $ranges = $this->rangeSource->rangesFor($crawler);

        return $ranges !== null
            && $this->rangeMatcher->matches($ip, $ranges);
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
