<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Jobs;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\ShardKeys;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\TimeHelper;

// TODO: Not tested yet
class StoreData implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function __construct(protected string $partitionKey,
                                protected Interval $interval,
                                protected ?Carbon $scanFrom = null)
    {
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $scanFrom = $this->scanFrom ?? Carbon::now()->subDay();
        $executedAt = time();

        $recorder = $this->getCounterRecorder();
        $store = $this->getCounterStore();

        $intervalSeconds = TimeHelper::intervalToSeconds($this->interval);

        // Scan from the given time
        // until timeout or reached the current time
        while ($scanFrom->lt(now())) {

            // Timeout check
            if ($this->isTimedOut($executedAt)) {
                return;
            }

            // Get max shard key length
            $maxShardKeyLength = $recorder->getMaxShardKeyLength(
                $this->partitionKey,
                $this->interval,
                $scanFromTimestamp = $scanFrom->getTimestamp()
            );

            // Loop through all shard keys
            // to collect shard records
            foreach (new ShardKeys($maxShardKeyLength) as $shardKey) {

                // Get shard records
                $shardRecords = $recorder->getShardRecords(
                    $this->partitionKey,
                    $this->interval,
                    $scanFromTimestamp,
                    $shardKey
                );

                // Perform upsert
                if ($shardRecords) {

                    // Upsert the records
                    if (!$store->upsert($shardRecords)) {
                        throw new \Exception('Failed to upsert counter records');
                    }

                    // If upsert succeeds, flush the records in this shard
                    $recorder->flush(
                        $this->partitionKey,
                        $this->interval,
                        $scanFromTimestamp,
                        $shardKey
                    );
                }

                // Timeout check
                if ($this->isTimedOut($executedAt)) {
                    return;
                }
            }

            // Flush the counter data in this time
            $recorder->flush(
                $this->partitionKey,
                $this->interval,
                $scanFromTimestamp
            );

            // Update the scan from time by +1 interval
            $scanFrom = $scanFrom->addSeconds($intervalSeconds);
        }
    }

    /**
     * @throws BindingResolutionException
     * @return Recorder
     */
    protected function getCounterRecorder()
    {
        return app()->make(Recorder::class);
    }

    /**
     * @throws BindingResolutionException
     * @return Store
     */
    protected function getCounterStore()
    {
        return app()->make(Store::class);
    }

    /**
     * Check if the job is timed out
     *
     * @param int $executedAt
     * @return bool
     */
    protected function isTimedOut(int $executedAt): bool
    {
        if (time() - $executedAt > $this->timeout - 5) {
            return true;
        }

        return false;
    }
}