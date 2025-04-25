<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Jobs\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder;

trait GetCounterRecorder
{
    protected ?string $counterRecorderDriver = null;

    /**
     * Set the recorder driver.
     *
     * @param string $driver
     * @return $this
     */
    public function setCounterRecorderDriver(string $driver): static
    {
        $this->counterRecorderDriver = $driver;
        return $this;
    }

    /**
     * @throws BindingResolutionException
     * @return Recorder
     */
    protected function getCounterRecorder(): Recorder
    {
        /** @var Recorder */
        return app()->make(Recorder::class)->driver($this->counterRecorderDriver);
    }
}