<?php

namespace KhanhArtisan\LaravelBackbone\Counter\Jobs\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;

trait GetCounterStore
{
    protected ?string $counterStoreDriver = null;

    /**
     * Set the store driver.
     *
     * @param string $driver
     * @return $this
     */
    public function setCounterStoreDriver(string $driver): static
    {
        $this->counterStoreDriver = $driver;
        return $this;
    }

    /**
     * @throws BindingResolutionException
     * @return Store
     */
    protected function getCounterStore(): Store
    {
        /** @var Store */
        return app()->make(Store::class)->driver($this->counterStoreDriver);
    }
}