<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Catalog\CrawlerDefinition;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\Dns\ForwardConfirmedReverseDnsVerifier;
use JacyImp\CrawlerVerifier\IpRange\IpRangeMatcher;
use JacyImp\CrawlerVerifier\IpRange\Source\IpRangeSource;
use JacyImp\CrawlerVerifier\VerificationMethod;

final readonly class BuiltInCrawlerProvider implements CrawlerProvider
{
    public function __construct(
        private BuiltInCrawlerCatalog $catalog,
        private IpRangeSource $rangeSource,
        private IpRangeMatcher $rangeMatcher = new IpRangeMatcher(),
        private ForwardConfirmedReverseDnsVerifier $dnsVerifier = new ForwardConfirmedReverseDnsVerifier(),
    ) {
    }

    public function identify(string $userAgent): ?Crawler
    {
        foreach ($this->catalog->all() as $definition) {
            foreach ($definition->userAgentTokens as $token) {
                if ($this->containsToken($userAgent, $token)) {
                    return $definition->crawler;
                }
            }
        }

        return null;
    }

    public function supports(CrawlerIdentity $crawler): bool
    {
        return $crawler instanceof Crawler
            && $this->catalog->find($crawler) !== null;
    }

    public function verify(
        CrawlerIdentity $crawler,
        string $ip,
    ): ?VerificationMethod {
        if (!$crawler instanceof Crawler) {
            return null;
        }

        $definition = $this->catalog->find($crawler);

        if ($definition === null) {
            return null;
        }

        if ($this->verifyIpRange($definition, $ip)) {
            return VerificationMethod::IpRange;
        }

        if (
            $definition->hasDnsVerification()
            && $this->dnsVerifier->verify(
                $ip,
                $definition->dnsSuffixes,
            )
        ) {
            return VerificationMethod::ForwardConfirmedReverseDns;
        }

        return null;
    }

    private function verifyIpRange(
        CrawlerDefinition $definition,
        string $ip,
    ): bool {
        if (!$definition->hasIpRangeFeed()) {
            return false;
        }

        $ranges = $this->rangeSource->rangesFor(
            $definition->crawler,
        );

        return $ranges !== null
            && $this->rangeMatcher->matches(
                $ip,
                $ranges,
            );
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
