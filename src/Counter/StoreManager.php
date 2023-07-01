<?php

namespace KhanhArtisan\LaravelBackbone\Counter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Manager;
use KhanhArtisan\LaravelBackbone\Counter\Stores\DatabaseStore;

class StoreManager extends Manager
{
    public function getDefaultDriver()
    {
        return config('counter.default_store');
    }

    protected function getConfig(string $driver): ?array
    {
        return config('counter.stores.'.$driver);
    }

    protected function createDatabaseDriver(): DatabaseStore
    {
        $config = $this->getConfig('database');
        $connection = $config['connection'];

        $connectionDriver = config('database.connections.'.$connection.'.driver');

        return new DatabaseStore(
            DB::connection($connection),
            $config['table'],
            $connectionDriver
        );
    }
}