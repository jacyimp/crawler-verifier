<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Ip;

use RuntimeException;

final class NativeIpRangeFetcher implements IpRangeFetcher
{
    public function fetch(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException(sprintf(
                'Invalid IP range URL "%s".',
                $url,
            ));
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
            throw new RuntimeException(sprintf(
                'Unable to fetch IP ranges from "%s".',
                $url,
            ));
        }

        return $contents;
    }
}
