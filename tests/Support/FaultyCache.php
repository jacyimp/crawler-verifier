<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Support;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class FaultyCache implements CacheInterface
{
    public function __construct(
        private readonly bool $throwOnGet = false,
        private readonly bool $rejectSet = false,
    ) {
    }
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->throwOnGet) {
            throw new FaultyCacheException();
        } return $default;
    }
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if ($this->rejectSet) {
            return false;
        }

        throw new FaultyCacheException();
    }
    public function delete(string $key): bool
    {
        return true;
    }
    public function clear(): bool
    {
        return true;
    }
    /**
     * @param iterable<mixed, mixed> $keys
     *
     * @return iterable<mixed, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return [];
    }
    /** @param iterable<mixed, mixed> $values */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return true;
    }
    /**
     * @param iterable<mixed, mixed> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }
    public function has(string $key): bool
    {
        return false;
    }
}
