<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

interface Recorder
{
    /**
     * Record
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param string|int $reference
     * @param int $value
     * @param int $shardSize
     * @param null|int $eventTime
     * @return bool
     */
    public function record(string $partitionKey,
                        Interval $interval,
                        string|int $reference,
                        int $value,
                        int $shardSize = 1000,
                        ?int $eventTime = null
    ): bool;

    /**
     * Get shard key of the given reference at a specified time
     * Return null if it does not exist
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param string|int $reference
     * @return ShardKey|null
     */
    public function getShardKeyForReference(string $partitionKey,
                                            Interval $interval,
                                            int $time,
                                            string|int $reference
    ): ?ShardKey;

    /**
     * Get max length of shard key at the given timestamp
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @return int
     */
    public function getMaxShardKeyLength(string $partitionKey, Interval $interval, int $time): int;

    /**
     * Get the current size of a shard at the given timestamp
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param ShardKey $shardKey
     * @return int
     */
    public function getShardSize(string $partitionKey,
                                 Interval $interval,
                                 int $time,
                                 ShardKey $shardKey): int;

    /**
     * Get all records in a shard
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param ShardKey $shardKey
     * @return array<Record>
     */
    public function getShardRecords(string $partitionKey,
                                 Interval $interval,
                                 int $time,
                                 ShardKey $shardKey
    ): array;

    /**
     * Flush data
     *
     * @param string $partitionKey
     * @param Interval|null $interval
     * @param int|null $time
     * @param ShardKey|null $shardKey
     * @return bool
     */
    public function flush(string $partitionKey,
                          ?Interval $interval = null,
                          ?int $time = null,
                          ?ShardKey $shardKey = null
    ): bool;
}