<?php

namespace KhanhArtisan\LaravelBackbone\Counter;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Manager;
use KhanhArtisan\LaravelBackbone\Counter\Recorders\RedisRecorder;

class RecorderManager extends Manager
{
    public function getDefaultDriver()
    {
        return config('counter.default_recorder');
    }

    protected function getConfig(string $driver): ?array
    {
        return config('counter.recorders.'.$driver);
    }

    protected function createRedisDriver(): RedisRecorder
    {
        $config = $this->getConfig('redis');
        $redisStore = new RedisStore(app('redis'), 'backbone-counter', $config['connection']);
        return new RedisRecorder($redisStore, $config);
    }
}