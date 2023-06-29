<?php

namespace KhanhArtisan\LaravelBackbone\Support\Facades\Counter;

use Illuminate\Support\Facades\Facade;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\ShardKey;

/**
 * @method bool record(string $partitionKey, Interval $interval, string|int $reference, int $value, int $shardSize = 1000, ?int $eventTime = null)
 * @method null|ShardKey getShardKeyForReference(string $partitionKey, Interval $interval, int $time, string|int $reference)
 * @method int getMaxShardKeyLength(string $partitionKey, Interval $interval, int $time)
 * @method int getShardSize(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey)
 * @method array getShardRecords(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey)
 * @method bool flush(string $partitionKey, ?Interval $interval, ?int $time = null, ?ShardKey $shardKey = null)
 */
class Recorder extends Facade
{
    public static function getFacadeAccessor()
    {
        return \KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder::class;
    }
}