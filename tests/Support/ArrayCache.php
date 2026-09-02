<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Support;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

final class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * @var array<string, int|null>
     */
    private array $expiresAt = [];

    /**
     * @var list<int|null>
     */
    private array $recordedTtls = [];

    public function get(
        string $key,
        mixed $default = null,
    ): mixed {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->values[$key];
    }

    public function set(
        string $key,
        mixed $value,
        null|int|DateInterval $ttl = null,
    ): bool {
        $seconds = $this->ttlSeconds($ttl);
        $this->recordedTtls[] = $seconds;

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        $this->values[$key] = $value;

        $this->expiresAt[$key] = $seconds === null
            ? null
            : time() + $seconds;

        return true;
    }

    public function delete(string $key): bool
    {
        unset(
            $this->values[$key],
            $this->expiresAt[$key],
        );

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->expiresAt = [];

        return true;
    }

    public function getMultiple(
        iterable $keys,
        mixed $default = null,
    ): iterable {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get(
                $key,
                $default,
            );
        }

        return $values;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(
        iterable $values,
        null|int|DateInterval $ttl = null,
    ): bool {
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Cache keys must be strings.');
            }

            $this->set(
                $key,
                $value,
                $ttl,
            );
        }

        return true;
    }

    /**
     * @return array<mixed>
     */
    public function getArray(string $key): array
    {
        $value = $this->get($key);

        if (!is_array($value)) {
            throw new \UnexpectedValueException(sprintf(
                'Cache value for "%s" is not an array.',
                $key,
            ));
        }

        return $value;
    }

    public function deleteMultiple(
        iterable $keys,
    ): bool {
        foreach ($keys as $key) {
            $this->delete(
                $key,
            );
        }

        return true;
    }

    /**
     * @return list<int|null>
     */
    public function recordedTtls(): array
    {
        return $this->recordedTtls;
    }

    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->values)) {
            return false;
        }

        $expiresAt = $this->expiresAt[$key];

        if (
            $expiresAt !== null
            && $expiresAt <= time()
        ) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    private function ttlSeconds(
        null|int|DateInterval $ttl,
    ): ?int {
        if ($ttl === null || is_int($ttl)) {
            return $ttl;
        }

        $now = new DateTimeImmutable();

        return $now->add($ttl)->getTimestamp()
            - $now->getTimestamp();
    }
}
