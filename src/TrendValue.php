<?php

namespace Flowframe\Trend;

use Carbon\CarbonImmutable;

class TrendValue
{
    public string $timezone;

    public string $format;

    public function __construct(
        public string $date,
        public string $interval,
        public mixed $aggregate,
    ) {}

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getLabel(): string
    {
        $dateTime = $this->getDateTime();

        if ($this->timezone) {
            $dateTime = $dateTime->timezone($this->timezone);
        }

        $format = $this->format ?? match ($this->interval) {
            'minute', 'minutes' => "Y-m-d H:i:00",
            'hour' => 'Y-m-d H:i',
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'year' => 'Y',
            default => throw new Error('Invalid interval.'),
        };

        return $dateTime->format($format);
    }

    public function getDateTime(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date);
    }
}
