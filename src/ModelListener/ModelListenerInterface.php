<?php

namespace KhanhArtisan\LaravelBackbone\ModelListener;

use Illuminate\Database\Eloquent\Model;

interface ModelListenerInterface
{
    /**
     * Listener with higher priority will run first
     *
     * @return int
     */
    public function priority(): int;

    /**
     * Determine if the listener should be singleton
     *
     * @return bool
     */
    public function isSingleton(): bool;

    /**
     * The model class to listen to
     *
     * @return string
     */
    public function modelClass(): string;

    /**
     * List of events that the listener will be fired.
     * Valid values are the method names of Laravel Observer (Ref: https://laravel.com/docs/eloquent#observers)
     * For example: creating, created, updating, updated, saving, saved, deleting, deleted, forceDeleted, restored
     *
     * @return array<string>
     */
    public function events(): array;

    /**
     * Handle the event
     *
     * @param Model $resource
     * @param string $event
     * @return void
     */
    public function handle(Model $resource, string $event): void;
}