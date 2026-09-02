<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

final readonly class VerificationResult
{
    private function __construct(
        public bool $is_verified,
        public ?CrawlerIdentity $crawler,
        public ?VerificationMethod $method,
    ) {
    }

    public static function verified(
        CrawlerIdentity $crawler,
        VerificationMethod $method,
    ): self {
        return new self(
            is_verified: true,
            crawler: $crawler,
            method: $method,
        );
    }

    public static function unverified(
        ?CrawlerIdentity $crawler = null,
    ): self {
        return new self(
            is_verified: false,
            crawler: $crawler,
            method: null,
        );
    }
}
