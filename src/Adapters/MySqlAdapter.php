<?php

namespace Flowframe\Trend\Adapters;

use Flowframe\Trend\Exceptions\TrendException;

class MySqlAdapter extends AbstractAdapter
{
    public function format(string $column, string $interval, int $intervalCount = 1): string
    {
        $format = match ($interval) {
            'minute', 'minutes' => '%Y-%m-%d %H:%i:00',
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => throw TrendException::invalidInterval($interval),
        };

        return "date_format({$column}, '{$format}')";
    }
}
