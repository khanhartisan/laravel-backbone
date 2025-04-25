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
use KhanhArtisan\LaravelBackbone\Counter\Jobs\Traits\GetCounterRecorder;
use KhanhArtisan\LaravelBackbone\Counter\Jobs\Traits\GetCounterStore;

class StoreData implements ShouldQueue
{
    use Queueable;
    use GetCounterRecorder, GetCounterStore;

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
            for ($i = 1; $i <= $maxShardKeyLength; $i++) {
                foreach (new ShardKeys($i) as $shardKey) {

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