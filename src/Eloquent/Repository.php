<?php

namespace KhanhArtisan\LaravelBackbone\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use KhanhArtisan\LaravelBackbone\GetQueryExecutors\DefaultExecutor;

/**
 * Default repository implementation.
 * You can extend this class and override any methods of it.
 */
class Repository implements RepositoryInterface
{
    /**
     * @param string $modelClass
     */
    public function __construct(protected string $modelClass)
    {
        if (!class_exists($this->modelClass)) {
            throw new \InvalidArgumentException($this->modelClass.' class is not found.');
        }

        if (!(new \ReflectionClass($this->modelClass))->isSubclassOf(Model::class)) {
            throw new \InvalidArgumentException($this->modelClass.' is not an eloquent model class.');
        }
    }

    /**
     * @inheritDoc
     */
    public function modelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @inheritDoc
     */
    public function query(): Builder
    {
        return call_user_func([$this->modelClass(), 'query']);
    }

    /**
     * @inheritDoc
     */
    public function find(string|int $id, array $queryScopes = [], array $resourceVisitors = []): ?Model
    {
        $query = $this->query();
        $query->whereKey($id);

        $this->applyQueryScopes($query, $queryScopes);

        if (!$resource = $query->first()) {
            return null;
        }

        $this->applyResourceVisitors($resource, $resourceVisitors);

        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function get(Builder $query, ?GetQueryExecutorInterface $executor = null, array $queryScopes = [], array $collectionVisitors = []): GetData
    {
        if (!$executor) {
            $executor = new DefaultExecutor();
        }

        $this->applyQueryScopes($query, $queryScopes);
        $getData = $executor->execute($query);
        $this->applyCollectionVisitors($getData->getCollection(), $collectionVisitors);
        return $getData;
    }

    /**
     * @inheritDoc
     */
    public function save(Model $resource, array $data = [], array $savingVisitors = [], array $savedVisitors = []): bool
    {
        $resource->fill($data);
        $this->applyResourceVisitors($resource, $savingVisitors);

        if ($resource->save()) {
            $this->applyResourceVisitors($resource, $savedVisitors);
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function create(array $data, array $creatingVisitors = [], array $createdVisitors = []): Model
    {
        /** @var Builder $eloquentQuery */
        $eloquentQuery = call_user_func([$this->modelClass(), 'query']);

        /** @var Model */
        return tap($eloquentQuery->newModelInstance(), fn ($instance) => $this->save($instance, $data, $creatingVisitors, $createdVisitors));
    }

    /**
     * @inheritDoc
     */
    public function delete(Model|int|string $resource, array $deletingVisitors = [], array $deletedVisitors = []): bool
    {
        if (!$resource instanceof Model and !$resource = $this->find($resource)) {
            return false;
        }

        $this->validateResource($resource);

        $this->applyResourceVisitors($resource, $deletingVisitors);

        if ($resource->delete()) {
            $this->applyResourceVisitors($resource, $deletedVisitors);
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function massDelete(Builder $query, array $queryScopes = []): bool
    {
        $this->applyQueryScopes($query, $queryScopes);

        return $query->delete();
    }

    /**
     * @inheritDoc
     */
    public function withoutEvents(\Closure $callback): mixed
    {
        return call_user_func([$this->modelClass(), 'withoutEvents'], $callback);
    }

    /**
     * @inheritDoc
     */
    public function applyQueryScopes(Builder $query, array $scopes): void
    {
        foreach ($scopes as $index => $scope) {

            // Scope is a closure
            if ($scope instanceof \Closure) {

                $reflection = new \ReflectionFunction($scope);
                $parameters = $reflection->getParameters();
                if (count($parameters) !== 2) {
                    throw new \InvalidArgumentException('Scope #'.$index.' must accept 2 parameters.');
                }

                // Validate first param
                if ((string) $parameters[0]->getType() !== Builder::class) {
                    throw new \InvalidArgumentException('Scope #'.$index.' first parameter type must be Illuminate\Database\Eloquent\Builder');
                }

                // Validate second param
                $secondParamType = (string) $parameters[1]->getType();
                $this->validateModelClass($secondParamType, 'Scope #'.$index.' second parameter type must be '.Model::class.' or subclass of it.');

                // Apply closure scope
                $query->withGlobalScope($index, function (Builder $query) use ($scope) {
                    $scope($query, $query->getModel());
                });
                continue;

            }

            // Scope object
            if (!$scope instanceof Scope) {
                throw new \InvalidArgumentException('Scope #'.$index.' is not an instance of Scope.');
            }

            // Apply scope
            $query->withGlobalScope($index, $scope);
        }
    }

    /**
     * @inheritDoc
     */
    public function applyResourceVisitors(Model $resource, array $resourceVisitors): void
    {
        $this->validateResource($resource);

        foreach ($resourceVisitors as $index => $resourceVisitor) {

            // Visitor is a closure
            if ($resourceVisitor instanceof \Closure) {
                $reflection = new \ReflectionFunction($resourceVisitor);
                $parameters = $reflection->getParameters();
                if (count($parameters) !== 1) {
                    throw new \InvalidArgumentException('Resource Visitor #'.$index.' must accept only 1 parameter.');
                }

                // Validate param type
                $this->validateModelClass((string) $parameters[0]->getType(), 'Resource Visitor #'.$index.' first parameter must be '.Model::class.' or its subclass.');

                // Apply Visitor
                $resourceVisitor($resource);
                continue;
            }

            // ResourceVisitor object
            if (!$resourceVisitor instanceof ResourceVisitorInterface) {
                throw new \InvalidArgumentException('Visitor #'.$index.' is not an instance of ResourceVisitorInterface.');
            }
            $resourceVisitor->apply($resource);
        }
    }

    /**
     * @inheritDoc
     */
    public function applyCollectionVisitors(Collection $collection, array $collectionVisitors): void
    {
        foreach ($collectionVisitors as $index => $collectionVisitor) {

            // Visitor is a closure
            if ($collectionVisitor instanceof \Closure) {
                $reflection = new \ReflectionFunction($collectionVisitor);
                $parameters = $reflection->getParameters();
                if (count($parameters) !== 1) {
                    throw new \InvalidArgumentException('Collection Visitor #'.$index.' must accept only 1 parameter.');
                }

                // Validate param type
                $this->validateClassIsSubclassOf(
                    (string) $parameters[0]->getType(),
                    Collection::class,
                    'Collection Visitor #'.$index.' first parameter type must be '.Collection::class.' or subclass of it.'
                );

                // Execute Visitor
                $collectionVisitor($collection);
                continue;
            }

            // Collection Visitor object
            if (!$collectionVisitor instanceof CollectionVisitorInterface) {
                throw new \InvalidArgumentException('Visitor #'.$index.' is not an instance of CollectionVisitorInterface.');
            }
            $collectionVisitor->apply($collection);
        }
    }

    /**
     * Validate resource
     *
     * @param Model $resource
     * @return void
     */
    protected function validateResource(Model $resource): void
    {
        if (get_class($resource) !== $this->modelClass()) {
            throw new \InvalidArgumentException('Cannot execute resource Visitor, resource must be an instance of '.$this->modelClass());
        }
    }

    /**
     * Validate model class
     *
     * @param string $modelClass
     * @param string|null $exceptionMessage
     * @return void
     */
    protected function validateModelClass(string $modelClass, ?string $exceptionMessage = null): void
    {
        $this->validateClassIsSubclassOf($modelClass, Model::class, $exceptionMessage);
    }

    /**
     * Validate a class is the same or subclass of another class
     *
     * @param string $class
     * @param string $compareToClass
     * @param string|null $exceptionMessage
     * @return void
     */
    protected function validateClassIsSubclassOf(string $class, string $compareToClass, ?string $exceptionMessage = null): void
    {
        if (!class_exists($compareToClass)) {
            throw new \InvalidArgumentException('Class '.$compareToClass.' does not exists.');
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException($exceptionMessage ?? $class.' class is not found.');
        }

        if ($class === $compareToClass) {
            return;
        }

        $reflectClass = new \ReflectionClass($class);
        if (!$reflectClass->isSubclassOf($compareToClass)) {
            throw new \InvalidArgumentException($exceptionMessage ?? $class.' is not a subclass of '.$compareToClass);
        }
    }
}