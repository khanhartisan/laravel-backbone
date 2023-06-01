<?php

namespace KhanhArtisan\LaravelBackbone\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

interface RepositoryInterface
{
    /**
     * Define the model class
     *
     * @return string
     */
    public function modelClass(): string;

    /**
     * Get new query builder
     *
     * @return Builder
     */
    public function query(): Builder;

    /**
     * Apply query scopes
     *
     * @param Builder $query
     * @param array<Scope|\Closure> $scopes
     * @return void
     */
    public function applyQueryScopes(Builder $query, array $scopes): void;

    /**
     * Apply resource Visitors
     *
     * @param Model $resource
     * @param array<ResourceVisitorInterface|\Closure> $resourceVisitors
     * @return void
     */
    public function applyResourceVisitors(Model $resource, array $resourceVisitors): void;

    /**
     * Apply collection Visitors
     *
     * @param Collection $collection
     * @param array<CollectionVisitorInterface|\Closure> $collectionVisitors
     * @return void
     */
    public function applyCollectionVisitors(Collection $collection, array $collectionVisitors): void;

    /**
     * Find a resource by its id
     *
     * @param string|int $id
     * @param array<Scope|\Closure> $queryScopes
     * @param array<ResourceVisitorInterface|\Closure> $resourceVisitors
     * @return Model|null
     */
    public function find(string|int $id, array $queryScopes = [], array $resourceVisitors = []): ?Model;

    /**
     * Get a collection of resources
     *
     * @param Builder $query
     * @param GetQueryExecutorInterface|null $executor
     * @param array<Scope|\Closure> $queryScopes
     * @param array<CollectionVisitorInterface|\Closure> $collectionVisitors
     * @return GetData
     */
    public function get(Builder $query, ?GetQueryExecutorInterface $executor = null, array $queryScopes = [], array $collectionVisitors = []): GetData;

    /**
     * Save a resource
     *
     * @param Model $resource
     * @param array $data
     * @param array<ResourceVisitorInterface|\Closure> $savingVisitors
     * @param array<ResourceVisitorInterface|\Closure> $savedVisitors
     * @return bool
     */
    public function save(Model $resource, array $data = [], array $savingVisitors = [], array $savedVisitors = []): bool;

    /**
     * Store a resource
     *
     * @param array $data
     * @param array<ResourceVisitorInterface|\Closure> $creatingVisitors
     * @param array<ResourceVisitorInterface|\Closure> $createdVisitors
     * @return Model
     */
    public function create(array $data, array $creatingVisitors = [], array $createdVisitors = []): Model;

    /**
     * Destroy a resource by its id or its instance
     *
     * @param int|string|Model $resource
     * @param array<ResourceVisitorInterface|\Closure> $deletingVisitors
     * @param array<ResourceVisitorInterface|\Closure> $deletedVisitors
     * @return bool
     */
    public function delete(int|string|Model $resource, array $deletingVisitors = [], array $deletedVisitors = []): bool;

    /**
     * Destroy the given query
     *
     * @param Builder $query
     * @param array<Scope|\Closure> $queryScopes
     * @return bool
     */
    public function massDelete(Builder $query, array $queryScopes = []): bool;

    /**
     * Execute without firing events
     *
     * @param \Closure $callback
     * @return mixed
     */
    public function withoutEvents(\Closure $callback): mixed;
}