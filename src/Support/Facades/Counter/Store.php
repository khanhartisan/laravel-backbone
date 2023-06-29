<?php

namespace KhanhArtisan\LaravelBackbone\Support\Facades\Counter;

use Illuminate\Support\Facades\Facade;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Record;

/**
 * @method bool upsert(Record|Record[] $records)
 * @method array syncIntervalRecords(Interval $fromInterval, Interval|array $toInterval, ?string $partitionKEy = null, ?int $limit = null)
 * @method array getRecordsAndExecuteOnce(string $partitionKey, Interval $interval, int $limit, \Closure $executor)
 * @method array getRecordsByTime(string $partitionKey, Interval $interval, int $time, int $limit = 100, string $sort = 'asc', string|int|null $cursorId = null)
 * @method array getRecordsByReference(string $partitionKey, Interval $interval, string|int $reference, int $fromTime, int $toTime)
 * @method null|Record getRecord(string $partitionKey, Interval $interval, int $time, string|int $reference)
 * @method bool delete(array|string $id)
 * @method bool prune(Interval $interval, int $time, ?string $partitionKey = null, ?int $limit = null)
 */
class Store extends Facade
{
    public static function getFacadeAccessor()
    {
        return \KhanhArtisan\LaravelBackbone\Contracts\Counter\Store::class;
    }
}