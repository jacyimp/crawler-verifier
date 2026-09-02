<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Update;

use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;

final class NativeIpRangeFetcher implements IpRangeFetcher
{
    public function fetch(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw IpRangeSourceException::invalidUrl($url);
        }

        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw IpRangeSourceException::unsupportedScheme($url);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => 1,
                'user_agent' => 'jacyimp/crawler-verifier',
            ],
        ]);

        $contents = @file_get_contents(
            $url,
            false,
            $context,
        );

        if ($contents === false) {
            throw IpRangeSourceException::unableToFetch($url);
        }

        return $contents;
    }
}
