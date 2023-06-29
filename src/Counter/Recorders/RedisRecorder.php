<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Recorders;

use Illuminate\Cache\RedisStore;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Record;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\ShardKey;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\TimeHelper;

class RedisRecorder implements Recorder
{
    public function __construct(protected RedisStore $redis, protected array $config)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function record(string $partitionKey,
                           Interval $interval,
                           int|string $reference,
                           int $value,
                           int $shardSize = 1000,
                           ?int $eventTime = null): bool
    {
        // Hit in silence
        try {

            $time = $eventTime ?: time();

            // Make hash from reference
            $hash = $this->makeHash($reference);

            // Record hit if shard key found
            if ($shardKey = $this->getShardKeyForReference($partitionKey, $interval, $time, $reference)) {
                $this->redis
                    ->tags($this->tags($partitionKey, $interval, $time, $shardKey))
                    ->increment($reference, $value);
                return true;
            }

            // Find a shard to record hit
            for ($i = 1; $i <= strlen($hash); $i++) {
                $shardKey = new ShardKey(substr($hash, 0, $i));

                // Skip if shard is full
                if ($currentShardSize = $this->getShardSize($partitionKey, $interval, $time, $shardKey)
                    and $currentShardSize >= $shardSize) {
                    continue;
                }

                // Register the reference key to the shard
                if (!$this->registerReferenceToShard($partitionKey, $interval, $time, $shardKey, $reference)) {
                    return false;
                }

                // Record hit
                $this->redis
                    ->tags($this->tags($partitionKey, $interval, $time, $shardKey))
                    ->put($reference, $value, $this->config['expiration']);

                // Update shard size
                if (!$currentShardSize) {
                    $this->redis
                        ->tags($this->tags($partitionKey, $interval, $time, $shardKey))
                        ->put($this->shardSizeCacheKey(), 1, $this->config['expiration']);
                } else {
                    $this->redis
                        ->tags($this->tags($partitionKey, $interval, $time, $shardKey))
                        ->increment($this->shardSizeCacheKey());
                }

                // Update max shard key length
                if ($i > $this->getMaxShardKeyLength($partitionKey, $interval, $time)) {
                    $this->redis
                        ->tags($this->tags($partitionKey, $interval, $time))
                        ->put($this->maxShardKeyLengthCacheKey(), $i, $this->config['expiration']);
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getShardKeyForReference(string $partitionKey,
                                            Interval $interval,
                                            int $time,
                                            int|string $reference): ?ShardKey
    {
        $hash = $this->makeHash($reference);

        for ($i = 1; $i <= $this->getMaxShardKeyLength($partitionKey, $interval, $time); $i++) {
            $shardKey = new ShardKey(substr($hash, 0, $i));
            if ($this->redis->tags($this->tags($partitionKey, $interval, $time, $shardKey))->has($reference)) {
                return $shardKey;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMaxShardKeyLength(string $partitionKey, Interval $interval, int $time): int
    {
        return intval(
            $this->redis
                ->tags($this->tags($partitionKey, $interval, $time))
                ->get($this->maxShardKeyLengthCacheKey())
        ) ?: 0;
    }

    /**
     * @inheritDoc
     */
    public function getShardSize(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey): int
    {
        return intval(
            $this->redis
                ->tags($this->tags($partitionKey, $interval, $time, $shardKey))
                ->get($this->shardSizeCacheKey())
        ) ?: 0;
    }

    /**
     * @inheritDoc
     */
    public function getShardRecords(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey): array
    {
        // Get shard references
        $references = $this->getShardReferences($partitionKey, $interval, $time, $shardKey);
        if (!$references) {
            return [];
        }

        $data = $this
            ->redis
            ->tags($this->tags($partitionKey, $interval, $time, $shardKey))
            ->many($references);

        return collect($data)
            ->map(fn ($value, $reference) => new Record(
                $partitionKey,
                $interval,
                $time,
                $reference,
                $value
            ))
            ->values()
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function flush(string $partitionKey,
                          ?Interval $interval = null,
                          ?int $time = null,
                          ?ShardKey $shardKey = null): bool
    {
        try {

            $tags = [sha1($this->keyPrefix($partitionKey))];

            if ($interval) {
                $tags = [sha1($this->keyPrefix(
                    $partitionKey
                    .$interval->value
                ))];

                if ($time) {
                    $pointOfTime = TimeHelper::startTime($interval, $time);
                    $tags = [sha1($this->keyPrefix(
                        $partitionKey
                        .$interval->value
                        .$pointOfTime
                    ))];

                    if ($shardKey) {
                        $tags = [sha1($this->keyPrefix(
                            $partitionKey
                            .$interval->value
                            .$pointOfTime
                            .$shardKey->getKey()
                        ))];
                    }
                }
            }

            $this->redis->tags($tags)->flush();

            return true;

        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Cache key for max shard key length
     *
     * @return string
     */
    protected function maxShardKeyLengthCacheKey(): string
    {
        return 'max-shard-key-length';
    }

    /**
     * Cache key for shard size
     *
     * @return string
     */
    protected function shardSizeCacheKey(): string
    {
        return 'size';
    }

    /**
     * Add prefix to cache key
     *
     * @param string|int $key
     * @return string
     */
    protected function keyPrefix(string|int $key): string
    {
        return 'rc|'.$key;
    }

    /**
     * Get all references in a shard
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param ShardKey $shardKey
     * @return array
     */
    protected function getShardReferences(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey): array
    {
        $data = $this->redis->tags($this->tags($partitionKey, $interval, $time, $shardKey))->get('references');
        return is_array($data) ? $data : [];
    }

    /**
     * Register a reference to a shard
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param ShardKey $shardKey
     * @param string $reference
     * @return bool
     */
    protected function registerReferenceToShard(string $partitionKey, Interval $interval, int $time, ShardKey $shardKey, string $reference): bool
    {
        $references = $this->getShardReferences($partitionKey, $interval, $time, $shardKey);

        // Ignore if reference already exists
        if (in_array($reference, $references)) {
            return true;
        }

        // Otherwise - lock and register
        $timeout = 3;
        $tags = $this->tags($partitionKey, $interval, $time, $shardKey);

        $lock = $this->redis->lock(sha1('lock|'.json_encode($tags)), $timeout);
        try {
            $lock->block($timeout);
            $references[] = $reference;
            $this->redis->tags($tags)->put('references', $references, config('counter.expiration'));
        } catch (\Exception $e) {
            return false;
        } finally {
            optional($lock)->release();
        }

        return true;
    }

    /**
     * Get cache tags for partitionKey/interval/time
     *
     * @param string $partitionKey
     * @param Interval $interval
     * @param int $time
     * @param ShardKey|null $shardKey
     * @return array
     */
    protected function tags(string $partitionKey, Interval $interval, int $time, ?ShardKey $shardKey = null): array
    {
        $timestamp = TimeHelper::startTime($interval, $time);
        $tags = [
            sha1($this->keyPrefix($partitionKey)),
            sha1($this->keyPrefix($partitionKey.$interval->value)),
            sha1($this->keyPrefix($partitionKey.$interval->value.$timestamp))
        ];

        if ($shardKey) {
            $tags[] = sha1($this->keyPrefix($partitionKey.$interval->value.$timestamp.$shardKey->getKey()));
        }

        return $tags;
    }

    /**
     * Return hash for the given reference string
     *
     * @param string $reference
     * @return string
     */
    protected function makeHash(string $reference): string
    {
        return sha1($reference);
    }
}