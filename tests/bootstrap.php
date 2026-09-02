<?php

declare(strict_types=1);

namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';
}

namespace JacyImp\CrawlerVerifier\Dns {
    use JacyImp\CrawlerVerifier\Tests\Support\NativeFunctions;

    function gethostbyaddr(string $ip): string|false
    {
        $result = NativeFunctions::$reverse === null
            ? \gethostbyaddr($ip)
            : (NativeFunctions::$reverse)($ip);

        return is_string($result) ? $result : false;
    }

    /** @return array<int, array<string, mixed>>|false */
    function dns_get_record(string $hostname, int $type): array|false
    {
        $result = NativeFunctions::$forward === null
            ? \dns_get_record($hostname, $type)
            : (NativeFunctions::$forward)($hostname, $type);

        if (!is_array($result)) {
            return false;
        }

        $records = [];

        foreach ($result as $record) {
            if (!is_array($record)) {
                continue;
            }

            $normalized = [];

            foreach ($record as $key => $value) {
                if (is_string($key)) {
                    $normalized[$key] = $value;
                }
            }

            $records[] = $normalized;
        }

        return $records;
    }
}

namespace JacyImp\CrawlerVerifier\IpRange\Update {
    use JacyImp\CrawlerVerifier\Tests\Support\NativeFunctions;

    /** @param resource|null $context */
    function file_get_contents(
        string $filename,
        bool $useIncludePath = false,
        mixed $context = null,
    ): string|false {
        $result = NativeFunctions::$fetch === null
            ? \file_get_contents($filename, $useIncludePath, $context)
            : (NativeFunctions::$fetch)($filename, $useIncludePath, $context);

        return is_string($result) ? $result : false;
    }
}

namespace JacyImp\CrawlerVerifier\IpRange\Source {
    use JacyImp\CrawlerVerifier\Tests\Support\NativeFunctions;

    function file_get_contents(string $filename): string|false
    {
        if (NativeFunctions::$read === null) {
            return \file_get_contents($filename);
        }

        $result = (NativeFunctions::$read)($filename);

        return is_string($result) ? $result : false;
    }
}
