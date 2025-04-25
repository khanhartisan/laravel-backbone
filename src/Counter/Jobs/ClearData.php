<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Jobs;

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
                                protected int $olderThanTime,
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
            $this->olderThanTime,
            $this->partitionKey,
            $this->limit
        );
    }
}