<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Exception;

use InvalidArgumentException;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Exception\CrawlerVerifierException;
use JacyImp\CrawlerVerifier\Exception\InvalidIpRangeDataException;
use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;
use JacyImp\CrawlerVerifier\Exception\IpRangeUpdateException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(InvalidConfigurationException::class)]
#[CoversClass(InvalidIpRangeDataException::class)]
#[CoversClass(IpRangeSourceException::class)]
#[CoversClass(IpRangeUpdateException::class)]
final class ExceptionHierarchyTest extends TestCase
{
    #[Test]
    public function itExposesASingleCatchablePackageExceptionContract(): void
    {
        $exceptions = [
            new InvalidConfigurationException('Configuration failed.'),
            new InvalidIpRangeDataException('Range data failed.'),
            new IpRangeSourceException('Range source failed.'),
            new IpRangeUpdateException('Range update failed.'),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(
                CrawlerVerifierException::class,
                $exception,
            );
        }
    }

    #[Test]
    public function itKeepsInvalidConfigurationExceptionsCompatibleWithInvalidArgumentException(): void
    {
        self::assertInstanceOf(
            InvalidArgumentException::class,
            new InvalidConfigurationException('Configuration failed.'),
        );
    }

    #[Test]
    public function itKeepsOperationalExceptionsCompatibleWithRuntimeException(): void
    {
        $exceptions = [
            new InvalidIpRangeDataException('Range data failed.'),
            new IpRangeSourceException('Range source failed.'),
            new IpRangeUpdateException('Range update failed.'),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(
                RuntimeException::class,
                $exception,
            );
        }
    }
}
