<?php

namespace KhanhArtisan\LaravelBackbone\Support\Facades\Counter;

use Illuminate\Support\Facades\Facade;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\ShardKey;

/**
 * @method bool record(string $partitionKey, Interval $interval, string|int $reference, int $value, int $shardSize = 1000, ?int $eventTime)
 * @method null|ShardKey getShardKeyForReference(string $partitionKey, Interval $interval, int $time, string|int $reference)
 * @method int // TODO: Continue from here
 */
class Recorder extends Facade
{
    public static function getFacadeAccessor()
    {
        return \KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder::class;
    }
}