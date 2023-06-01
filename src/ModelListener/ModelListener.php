<?php

namespace KhanhArtisan\LaravelBackbone\ModelListener;

use Illuminate\Database\Eloquent\Model;

abstract class ModelListener implements ModelListenerInterface
{
    /**
     * Ensure the _handle(ModelType $resource, string $event) method is defined.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (!method_exists($this, '_handle')) {
            throw new \Exception('Method _handle() is not defined.');
        }

        $handleMethodReflect = new \ReflectionMethod($this, '_handle');
        $parameters = $handleMethodReflect->getParameters();
        if (count($parameters) > 2) {
            throw new \Exception('The "_handle" method should accept max 2 parameters.');
        }
        $paramType = $parameters[0]->getType()?->getName() ?? '"blank"';
        if ($paramType !== $this->modelClass()) {
            throw new \Exception('The "_handle" method\'s first parameter type must be '.$this->modelClass().', '.$paramType.' detected.');
        }

        if (count($parameters) === 2) {
            $secondParamType = $parameters[1]->getType()?->getName() ?? '"blank"';
            if ($secondParamType !== 'string') {
                throw new \Exception('The "handle" method\'s first parameter type must be string, '.$secondParamType.' detected.');
            }
        }
    }

    /**
     * @inheritDoc
     */
    abstract public function priority(): int;

    /**
     * @inheritDoc
     */
    abstract public function modelClass(): string;

    /**
     * @inheritDoc
     */
    abstract public function events(): array;

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function handle(Model $resource, string $event): void
    {
        if (get_class($resource) !== $this->modelClass()
            and !is_subclass_of($resource, $this->modelClass())
        ) {
            throw new \InvalidArgumentException('The given resource is not an instance of '.$this->modelClass());
        }

        if (!method_exists($this, '_handle')) {
            throw new \Exception('The _handle() method does not exists.');
        }

        $this->_handle($resource, $event);
    }
}