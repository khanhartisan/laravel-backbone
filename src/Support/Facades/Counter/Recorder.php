<?php

namespace KhanhArtisan\LaravelBackbone\Support\Facades\Counter;

use Illuminate\Support\Facades\Facade;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\ShardKey;

/**
 * @method static \KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder driver(?string $driver)
 * @method static bool record(string $partitionKey, Interval $interval, string|int $reference, int $value, int $shardSize = 1000, ?int $eventTime = null)
 * @method static null|ShardKey getShardKeyForReference(string $partitionKey, Interval $interval, int $time, string|int $reference)
 * @method static int getMaxShardKeyLength(string $partitionKey, Interval $interval, int $time)
 * @method static int getShardSize(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey)
 * @method static array getShardRecords(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey)
 * @method static bool flush(string $partitionKey, ?Interval $interval = null, ?int $time = null, ?ShardKey $shardKey = null)
 */
class Recorder extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return \KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder::class;
    }
}