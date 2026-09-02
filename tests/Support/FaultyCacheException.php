<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Support;

use Psr\SimpleCache\CacheException;
use RuntimeException;

final class FaultyCacheException extends RuntimeException implements CacheException
{
}
