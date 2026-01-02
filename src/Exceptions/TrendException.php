<?php

namespace Flowframe\Trend\Exceptions;

use RuntimeException;

class TrendException extends RuntimeException
{
    public static function unsupportedDriver(string $driverName): static
    {
        return new static("The driver $driverName is not supported.");
    }

    public static function invalidInterval(string $interval): static
    {
        return new static("Invalid interval provided: $interval");
    }
}
