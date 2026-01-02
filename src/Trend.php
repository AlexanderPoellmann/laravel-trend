<?php

namespace Flowframe\Trend;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Flowframe\Trend\Adapters\MySqlAdapter;
use Flowframe\Trend\Adapters\PgsqlAdapter;
use Flowframe\Trend\Adapters\SqliteAdapter;
use Flowframe\Trend\Exceptions\TrendException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Trend
{
    public string $interval;

    public int $intervalCount = 1;

    public CarbonInterface $start;

    public CarbonInterface $end;

    public string $dateColumn = 'created_at';

    public string $dateAlias = 'date';

    public function __construct(public Builder $builder) {}

    public static function query(Builder $builder): self
    {
        return new static($builder);
    }

    public static function model(string $model): self
    {
        return new static($model::query());
    }

    public function between($start, $end): self
    {
        $this->start = $start;
        $this->end = $end;

        return $this;
    }

    public function interval(string $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function intervalCount(int $intervalCount = 1): self
    {
        $this->intervalCount = $intervalCount;

        return $this;
    }

    public function perMinute(): self
    {
        return $this->interval('minute');
    }

    public function perMinutes(int $minutes): self
    {
        if ($minutes < 1) {
            $minutes = 1;
        }

        if ($minutes === 1) {
            return $this->perMinute();
        }

        return $this->intervalCount($minutes)->interval('minutes');
    }

    public function perQuarterOfHour(): self
    {
        return $this->perMinutes(15);
    }

    public function perHour(): self
    {
        return $this->interval('hour');
    }

    public function perDay(): self
    {
        return $this->interval('day');
    }

    public function perWeek(): self
    {
        return $this->interval('week');
    }

    public function perMonth(): self
    {
        return $this->interval('month');
    }

    public function perYear(): self
    {
        return $this->interval('year');
    }

    public function dateColumn(string $column): self
    {
        $this->dateColumn = $column;

        return $this;
    }

    public function dateAlias(string $alias): self
    {
        $this->dateAlias = $alias;

        return $this;
    }

    public function aggregate(string $column, string $aggregate): Collection
    {
        $query = $this->builder
            ->toBase();

        if ($this->interval === 'minutes' && $this->getDriverName() !== 'pgsql') {
            $query = $query->whereRaw("MINUTE($column) % $this->intervalCount = 0");
        }

        $values = $query
            ->selectRaw("
                {$this->getSqlDate()} as $this->dateAlias,
                $aggregate($column) as aggregate
            ")
            ->whereBetween($this->dateColumn, [$this->start, $this->end])
            ->groupBy($this->dateAlias)
            ->orderBy($this->dateAlias)
            ->get();

        return $this->mapValuesToDates($values);
    }

    public function average(string $column): Collection
    {
        return $this->aggregate($column, 'avg');
    }

    public function min(string $column): Collection
    {
        return $this->aggregate($column, 'min');
    }

    public function max(string $column): Collection
    {
        return $this->aggregate($column, 'max');
    }

    public function sum(string $column): Collection
    {
        return $this->aggregate($column, 'sum');
    }

    public function count(string $column = '*'): Collection
    {
        return $this->aggregate($column, 'count');
    }

    public function mapValuesToDates(Collection $values): Collection
    {
        $values = $values->map(fn ($value) => new TrendValue(
            date: $value->{$this->dateAlias},
            interval: $this->interval,
            aggregate: $value->aggregate,
        ));

        $placeholders = $this->getDatePeriod()->map(
            fn (CarbonInterface $date) => new TrendValue(
                date: $date->format($this->getCarbonDateFormat()),
                interval: $this->interval,
                aggregate: 0,
            )
        );

        return $values
            ->merge($placeholders)
            ->unique('date')
            ->sort()
            ->flatten();
    }

    protected function getDatePeriod(): Collection
    {
        return collect(
            CarbonPeriod::between(
                $this->start,
                $this->end,
            )->interval($this->getCarbonInterval())
        );
    }

    protected function getDriverName(): string
    {
        return $this->builder->getConnection()->getDriverName();
    }

    protected function getSqlDate(): string
    {
        $adapter = match ($this->getDriverName()) {
            'mysql', 'mariadb' => new MySqlAdapter(),
            'sqlite' => new SqliteAdapter(),
            'pgsql' => new PgsqlAdapter(),
            default => throw TrendException::unsupportedDriver($this->getDriverName()),
        };

        return $adapter->format($this->dateColumn, $this->interval, $this->intervalCount);
    }

    protected function getCarbonDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    protected function getCarbonInterval(): string
    {
        return "$this->intervalCount $this->interval";
    }
}
