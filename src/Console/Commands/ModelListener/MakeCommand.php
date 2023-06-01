<?php

namespace KhanhArtisan\LaravelBackbone\Console\Commands\ModelListener;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeCommand extends GeneratorCommand
{
    protected $name = 'make:model-listener';

    protected $description = 'Create a new model listener class (Laravel Backbone)';

    protected $type = 'ModelListener';

    protected $relativePath;

    protected function getStub()
    {
        return __DIR__.'/stubs/model_listener.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\'.$this->getRelativePath();
    }

    protected function getRelativePath(): string
    {
        if (isset($this->relativePath)) {
            return $this->relativePath;
        }

        $modelName = explode('\\', $this->argument('model'));
        $modelName = end($modelName);

        $defaultPath = 'ModelListeners\\'.$modelName;

        if ($events = $this->parseEvents() and $events->count() === 1) {
            $defaultPath .= '\\'.Str::title($events->first());
        }

        if (!$path = $this->argument('path')) {
            $path = $this->ask('Where should the file live in?', $defaultPath);
        }

        return $this->relativePath = $path;
    }

    protected function buildClass($name)
    {
        // Validate model class
        $modelClass = $this->argument('model');
        $modelClass = explode('\\', $modelClass);
        $modelClass = array_filter($modelClass);
        $modelName = end($modelClass);
        $modelClass = implode('\\', $modelClass);
        $modelClassReflect = new \ReflectionClass($modelClass);
        if (!$modelClassReflect->isSubclassOf(Model::class)) {
            $this->error($modelClass.' is not an Eloquent Model.');
            exit;
        }

        $replace = [
            '{{ priority }}' => $this->option('priority') ?? 0,
            '{{ modelClass }}' => $modelClass,
            '{{ modelName }}' => $modelName,
            '{{ modelVariableName }}' => Str::camel($modelName),
            '{{ events }}' => $this->parseEvents()
        ];

        return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
    }

    protected function parseEvents(): Collection
    {
        $modelClass = $this->argument('model');
        /** @var Model $model */
        $model = new $modelClass();
        $modelEvents = $model->getObservableEvents();

        $events = $this->argument('events');
        $events = collect(explode(',', $events));
        return $events->map(function ($event) use ($modelEvents, $modelClass) {
            $event = trim($event);
            if (!in_array($event, $modelEvents)) {
                $this->error($event.' is not a valid event. Valid events for '.$modelClass.' are: '.implode(', ', $modelEvents));
            }
            return $event;
        })->sort()->values();
    }

    protected function getOptions()
    {
        return [
            ['priority', 0, InputOption::VALUE_OPTIONAL, 'Priority score (higher will run first)'],
        ];
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'Name of the model listener'],
            ['model', InputArgument::REQUIRED, 'Model class (ie: App\Models\User)'],
            ['events', InputArgument::REQUIRED, 'Events separated by comma (ie: saved,deleted,restored...)'],
            ['path', InputArgument::OPTIONAL, 'Where should the file located?'],
        ];
    }
}