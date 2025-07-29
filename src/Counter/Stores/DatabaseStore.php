<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Stores;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Record;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\TimeHelper;
use KhanhArtisan\LaravelBackbone\Support\Enum;

class DatabaseStore implements Store
{
    public function __construct(protected Connection $connection, protected string $table = 'counter', protected string $driver = 'mysql')
    {
        if (!in_array($this->driver, ['mysql', 'pgsql'])) {
            throw new \InvalidArgumentException('Connection driver '.$this->driver.' is not supported.');
        }
    }

    /**
     * @inheritDoc
     */
    public function upsert(array|Record $records): bool
    {
        if ($records instanceof Record) {
            $records = [$records];
        }

        return $this->connection
            ->table($this->table)
            ->upsert(
                collect($records)
                    ->map(function (Record $record) {
                        return [
                            'id' => Str::ulid(),
                            'partition' => $record->getPartitionKey(),
                            'interval' => $record->getInterval()->value,
                            'time' => $record->getTime(),
                            'reference' => strval($record->getReference()),
                            'value' => $record->getValue(),
                        ];
                    })
                    ->toArray(),
                [
                    'partition', 'interval', 'time', 'reference'
                ],
                [
                    'value' => DB::raw($this->table.'.value + '.($this->driver === 'pgsql' ? 'excluded.value' : 'VALUES(`value`)'))
                ]
            );
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function rollupIntervalRecords(Interval $fromInterval,
                                        Interval|array $toInterval,
                                        ?string $partitionKey = null,
                                        ?int $limit = null): array
    {
        $connection = $this->connection;

        $fromRecords = null;
        $connection->transaction(function () use ($partitionKey, $fromInterval, $toInterval, $limit, &$fromRecords, $connection) {

            if (!is_array($toInterval)) {
                $toInterval = [$toInterval];
            }

            // Get list of records to sync
            $query = $connection
                ->table($this->table)
                ->where('is_rolled_up', false)
                ->where('interval', $fromInterval->value)
                ->where('time', '<', TimeHelper::startTime($fromInterval))
                ->orderBy('time');

            if ($partitionKey) {
                $query->where('partition', $partitionKey);
            }

            if ($limit) {
                $query->take($limit);
            }

            $fromRecords = $query->lockForUpdate()->get();

            // Return if no records
            if (!$fromRecords->count()) {
                return [];
            }

            // Sync
            foreach ($toInterval as $_toInterval) {

                // Build upsert data
                $upsertData = [];
                foreach ($fromRecords as $fromRecord) {

                    if (!isset($upsertData[$fromRecord->partition])) {
                        $upsertData[$fromRecord->partition] = [];
                    }

                    if (!isset($upsertData[$fromRecord->partition][$fromRecord->reference])) {
                        $upsertData[$fromRecord->partition][$fromRecord->reference] = [];
                    }

                    $toRecordTime = TimeHelper::startTime($_toInterval, $fromRecord->time);
                    if (!isset($upsertData[$fromRecord->partition][$fromRecord->reference][$toRecordTime])) {
                        $upsertData[$fromRecord->partition][$fromRecord->reference][$toRecordTime] = $fromRecord->value;
                    } else {
                        $upsertData[$fromRecord->partition][$fromRecord->reference][$toRecordTime] += $fromRecord->value;
                    }
                }

                // Build upsert records
                $upsertRecords = [];
                foreach ($upsertData as $partition => $referenceTimeValue) {
                    foreach ($referenceTimeValue as $reference => $timeValue) {
                        foreach ($timeValue as $_time => $_value) {
                            $upsertRecords[] = new Record(
                                $partition,
                                $_toInterval,
                                $_time,
                                $reference,
                                $_value
                            );
                        }
                    }
                }

                $this->upsert($upsertRecords);
            }

            // Update flag
            $connection
                ->table($this->table)
                ->whereIn('id', $fromRecords->pluck('id')->toArray())
                ->update([
                    'is_rolled_up' => true
                ]);

        });

        return $fromRecords->map(fn ($record) => new Record(
            $record->partition,
            Enum::fromValue(Interval::class, $record->interval),
            $record->time,
            $record->reference,
            $record->value,
            $record->id
        ))->values()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getRecordsAndExecuteOnce(string $partitionKey,
                                             Interval $interval,
                                             int $limit,
                                             Closure $executor): array
    {
        $records = [];

        $this->connection->transaction(function () use ($partitionKey, $interval, $limit, $executor, &$records) {

            $records = $this
                ->connection
                ->table($this->table)
                ->where('partition', $partitionKey)
                ->where('interval', $interval->value)
                ->where('is_executed', false)
                ->where('time', '<', TimeHelper::startTime($interval))
                ->orderBy('time')
                ->take($limit)
                ->lockForUpdate()
                ->get()
                ->map(fn ($record) => new Record(
                    $partitionKey,
                    $interval,
                    $record->time,
                    $record->reference,
                    $record->value,
                    $record->id
                ))
                ->toArray();

            if (!$records) {
                return;
            }

            $executor($records, fn () => $this->connection
                ->table($this->table)
                ->whereIn('id', collect($records)->map(fn (Record $record) => $record->getStoreReference())->toArray())
                ->update([
                    'is_executed' => true
                ])
            );

        });

        return $records;
    }

    /**
     * @inheritDoc
     */
    public function getRecordsByTime(string $partitionKey,
                                     Interval $interval,
                                     int $time,
                                     int $limit = 100,
                                     string $sort = 'asc',
                                     int|string|null $cursorId = null): array
    {
        $time = TimeHelper::startTime($interval, $time);

        $query = $this
            ->connection
            ->table('counter')
            ->where('partition', $partitionKey)
            ->where('interval', $interval->value)
            ->where('time', $time)
            ->orderBy('value', $sort === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', $sort === 'asc' ? 'asc' : 'desc')
            ->take($limit);

        // Cursor
        if ($cursorId
            and $cursor = $this->connection->table($this->table)->find($cursorId)
        ) {
            $query->where(
                fn (Builder $subQuery) => $subQuery
                    ->where('value', $sort === 'asc' ? '>' : '<', $cursor->value)
                    ->orWhere(
                        fn (Builder $subQuery2) => $subQuery2
                            ->where('value', $cursor->value)
                            ->where('id', $sort === 'asc' ? '>' : '<', $cursor->id)
                    )
            );
        }

        return $query
            ->get()
            ->map(fn ($record) => new Record(
                $partitionKey,
                $interval,
                $time,
                $record->reference,
                $record->value,
                $record->id
            ))
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getRecordsByReference(string $partitionKey,
                                          Interval $interval,
                                          int|string $reference,
                                          int $fromTime,
                                          int $toTime): array
    {
        return $this
            ->connection
            ->table($this->table)
            ->where('partition', $partitionKey)
            ->where('interval', $interval->value)
            ->where('reference', $reference)
            ->where('time', '>=', $fromTime)
            ->where('time', '<=', $toTime)
            ->orderBy('time')
            ->get()
            ->map(fn ($record) => new Record(
                $partitionKey,
                $interval,
                $record->time,
                $reference,
                $record->value,
                $record->id
            ))
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getRecord(string $partitionKey,
                              Interval $interval,
                              int $time,
                              int|string $reference): ?Record
    {
        $time = TimeHelper::startTime($interval, $time);

        if ($record = $this
            ->connection
            ->table($this->table)
            ->where('partition', $partitionKey)
            ->where('interval', $interval->value)
            ->where('time', $time)
            ->where('reference', $reference)
            ->first()
        ) {
            return new Record(
                $partitionKey,
                $interval,
                $time,
                $reference,
                $record->value,
                $record->id
            );
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function delete(array|string $id): bool
    {
        if (!is_array($id)) {
            $id = [$id];
        }

        $ids = collect($id)
            ->map(fn ($i) => strval($i))
            ->filter(fn ($i) => is_string($i) or is_int($i))
            ->values()
            ->toArray();

        if (!$ids) {
            return false;
        }

        return $this->connection->table('counter')->whereIn('id', $ids)->delete();
    }

    /**
     * @inheritDoc
     */
    public function prune(Interval $interval, int $time, ?string $partitionKey = null, ?int $limit = null): bool
    {
        $query = $this
            ->connection
            ->table($this->table)
            ->where('interval', $interval->value)
            ->where('time', '<=', $time);

        if ($partitionKey) {
            $query->where('partition', $partitionKey);
        }

        try {

            // Delete limit
            if ($limit) {

                // Mysql
                if ($this->driver === 'mysql') {
                    $query->take($limit);
                    $query->delete();
                }

                // Pgsql
                if ($this->driver === 'pgsql') {

                    $chunkSize = 1000;
                    while ($limit > 0) {

                        // Get ids to delete
                        $ids = $query
                            ->select('id')
                            ->take(min($limit, $chunkSize))
                            ->get()
                            ->pluck('id');

                        // Stop if no ids
                        if (!$idsCount = $ids->count()) {
                            break;
                        }

                        // Delete by ids
                        $this
                            ->connection
                            ->table($this->table)
                            ->whereIn('id', $ids)
                            ->delete();

                        // Decrease limit
                        $limit -= $idsCount;
                    }
                }

            }
            // Delete without limit
            else {
                $query->delete();
            }

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}