<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

use Closure;

interface Store
{
    /**
     * Upsert record data
     *
     * @param Record|Record[] $records
     * @return bool
     */
    public function upsert(Record|array $records): bool;

    /**
     * Roll-up interval records.
     * For example, roll up from 5m -> 10m, 10m -> 1h, 1h -> daily...
     * A record will only be executed once and never again.
     * Hint: Need a dedicated flag for this.
     *
     * @param Interval $fromInterval
     * @param Interval|array<Interval> $toInterval
     * @param null|string $partitionKey
     * @param null|int $limit
     * @return array Array of synced records
     */
    public function rollupIntervalRecords(Interval $fromInterval, Interval|array $toInterval, ?string $partitionKey = null, ?int $limit = null): array;

    /**
     * Get records and execute only once for each record.
     * An executed record will not be fetched by this function again.
     * The executor will receive an array<Record> as it's first parameter, and a closure as it's second parameter to mark all the records as executed.
     * This function will be used to sync records from counter data -> main data.
     * Hint: Need a dedicated flag for this.
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $limit
     * @param Closure $executor
     * @return array Array of executed records
     */
    public function getRecordsAndExecuteOnce(string $partitionKey, Interval $interval, int $limit, Closure $executor): array;

    /**
     * Get an array of records
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param int $limit
     * @param string $sort 'asc' or 'desc'
     * @param string|int|null $cursorId
     * @return array<Record>
     */
    public function getRecordsByTime(string $partitionKey, Interval $interval, int $time, int $limit = 100, string $sort = 'asc', string|int|null $cursorId = null): array;

    /**
     * Get an array of records by reference
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param string|int $reference
     * @param int $fromTime
     * @param int $toTime
     * @return array<Record>
     */
    public function getRecordsByReference(string $partitionKey, Interval $interval, string|int $reference, int $fromTime, int $toTime): array;

    /**
     * Get a record
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param string|int $reference
     * @return Record|null
     */
    public function getRecord(string $partitionKey, Interval $interval, int $time, string|int $reference): ?Record;

    /**
     * Delete records
     *
     * @param array|string $id
     * @return bool
     */
    public function delete(array|string $id): bool;

    /**
     * Prune all records that older than the given time.
     *
     * @param Interval $interval
     * @param int $time
     * @param null|string $partitionKey
     * @param null|int $limit
     * @return bool
     */
    public function prune(Interval $interval, int $time, ?string $partitionKey = null, ?int $limit = null): bool;
}