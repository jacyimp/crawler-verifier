<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Ip;

use JacyImp\CrawlerVerifier\Crawler;
use RuntimeException;

final readonly class DirectoryIpRangeSource implements IpRangeSource
{
    public function __construct(
        private string $directory,
        private JsonIpRangeParser $parser = new JsonIpRangeParser(),
    ) {
    }

    public function rangesFor(Crawler $crawler): ?array
    {
        $path = sprintf(
            '%s/%s.json',
            rtrim($this->directory, '/\\'),
            $crawler->value,
        );

        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf(
                'Unable to read IP ranges from "%s".',
                $path,
            ));
        }

        return $this->parser->parse($contents);
    }
}
