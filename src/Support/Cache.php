<?php

namespace KhanhArtisan\LaravelBackbone\Support;

use Illuminate\Support\Facades\Cache as LaravelCache;

class Cache
{
    /**
     * Try to get a cache by the given key.
     * If not found, call the user given function, put the result to the cache with the same key and return.
     *
     * @param string $key
     * @param callable $callable
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param string|null $store
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getOrCall(string $key, callable $callable, \DateTimeInterface|\DateInterval|int|null $ttl = null, ?string $store = null): mixed
    {
        $cacheInstance = LaravelCache::store($store);
        if ($cacheInstance->has($key)) {
            return $cacheInstance->get($key);
        }

        $result = call_user_func($callable);

        // Put result to cache
        $cacheInstance->put($key, $result, $ttl);

        return $result;
    }
}