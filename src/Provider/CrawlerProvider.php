<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\VerificationMethod;

interface CrawlerProvider
{
    public function identify(string $userAgent): ?CrawlerIdentity;

    public function supports(CrawlerIdentity $crawler): bool;

    public function verify(
        CrawlerIdentity $crawler,
        string $ip,
    ): ?VerificationMethod;
}
