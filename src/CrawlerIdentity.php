<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

interface CrawlerIdentity
{
    public function id(): string;
}
