<?php

namespace Flowframe\Trend\Adapters;

use Flowframe\Trend\Exceptions\TrendException;

/**
 * @see https://www.postgresql.org/docs/18/functions-datetime.html#FUNCTIONS-DATETIME-TRUNC
 * @see https://www.postgresql.org/docs/18/functions-datetime.html#FUNCTIONS-DATETIME-BIN
 */
class PgsqlAdapter extends AbstractAdapter
{
    public array $precisions = [
        'milliseconds',
        'second',
        'minute',
        'hour',
        'day',
        'week',
        'month',
        'quarter',
        'year',
        'decade',
        'century',
        'millennium',
    ];

    public function format(string $column, string $interval, int $intervalCount = 1): string
    {
        if ($interval === 'minutes') {
            return "date_bin('$intervalCount $interval', \"$column\", TIMESTAMP '1900-01-01')";
        }

        if (! in_array($interval, $this->precisions)) {
            throw TrendException::invalidInterval($interval);
        }

        return "date_trunc('$interval', \"$column\")";
    }
}
