<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\VerificationMethod;

interface CrawlerProvider
{
    public function identify(string $userAgent): ?Crawler;

    public function supports(Crawler $crawler): bool;

    public function verify(Crawler $crawler, string $ip): ?VerificationMethod;
}
