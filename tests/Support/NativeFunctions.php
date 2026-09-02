<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Support;

use Closure;

final class NativeFunctions
{
    public static ?Closure $reverse = null;

    public static ?Closure $forward = null;

    public static ?Closure $fetch = null;

    public static ?Closure $read = null;

    public static function reset(): void
    {
        self::$reverse = null;
        self::$forward = null;
        self::$fetch = null;
        self::$read = null;
    }
}
