<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Jobs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Counter\Jobs\Traits\GetCounterStore;

class ClearData implements ShouldQueue
{
    use Queueable;
    use GetCounterStore;

    public function __construct(protected Interval $interval,
                                protected int|CarbonInterface $olderThanTime,
                                protected ?string $partitionKey,
                                protected ?int $limit = null)
    {

    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $this->getCounterStore()->prune(
            $this->interval,
            $this->olderThanTime instanceof CarbonInterface
                ? $this->olderThanTime->getTimestamp()
                : $this->olderThanTime,
            $this->partitionKey,
            $this->limit
        );
    }
}