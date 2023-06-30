<?php

namespace KhanhArtisan\LaravelBackbone\Support\Facades\Counter;

use Illuminate\Support\Facades\Facade;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Record;

/**
 * @method static \KhanhArtisan\LaravelBackbone\Contracts\Counter\Store driver(?string $driver)
 * @method static bool upsert(Record|Record[] $records)
 * @method static array syncIntervalRecords(Interval $fromInterval, Interval|array $toInterval, ?string $partitionKEy = null, ?int $limit = null)
 * @method static array getRecordsAndExecuteOnce(string $partitionKey, Interval $interval, int $limit, \Closure $executor)
 * @method static array getRecordsByTime(string $partitionKey, Interval $interval, int $time, int $limit = 100, string $sort = 'asc', string|int|null $cursorId = null)
 * @method static array getRecordsByReference(string $partitionKey, Interval $interval, string|int $reference, int $fromTime, int $toTime)
 * @method static null|Record getRecord(string $partitionKey, Interval $interval, int $time, string|int $reference)
 * @method static bool delete(array|string $id)
 * @method static bool prune(Interval $interval, int $time, ?string $partitionKey = null, ?int $limit = null)
 */
class Store extends Facade
{
    public static function getFacadeAccessor()
    {
        return \KhanhArtisan\LaravelBackbone\Contracts\Counter\Store::class;
    }
}