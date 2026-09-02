<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Source;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;
use JacyImp\CrawlerVerifier\IpRange\JsonIpRangeParser;

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
            throw IpRangeSourceException::unableToRead($path);
        }

        return $this->parser->parse($contents);
    }
}
