<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

enum VerificationMethod: string
{
    case IpRange = 'ip_range';
    case ForwardConfirmedReverseDns = 'forward_confirmed_reverse_dns';
}
