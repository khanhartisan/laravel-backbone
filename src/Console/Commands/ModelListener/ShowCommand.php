<?php

namespace KhanhArtisan\LaravelBackbone\Console\Commands\ModelListener;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerManager;
use KhanhArtisan\LaravelBackbone\ModelListener\Observer;

class ShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model-listener:show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the list of observed models and model listeners';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $registeredModels = Observer::getRegisteredModels();
        $this->info('List of registered models: '.count($registeredModels));
        foreach ($registeredModels as $class) {
            $this->line($class);
        }

        $this->info('Event Listeners Map:');

        /** @var ModelListenerManager $modelListenerManager */
        $modelListenerManager = app()->make(ModelListenerManager::class);
        $map = $modelListenerManager->getMap();

        foreach ($map as $modelClass => $data) {
            $this->line($modelClass);
            if (!Observer::isRegistered($modelClass)) {
                $this->warn($modelClass.' model is not registered, the listeners may not be triggered.');
            }
            foreach ($data as $event => $listeners) {
                $this->line('     Event "'.$event.'": '.count($listeners).' '.Str::plural('listener', count($listeners)));
                foreach ($listeners as $sortKey => $listener) {
                    $this->line('           Priority '.(new $listener)->priority().': '.$listener);
                }
            }
        }
    }
}
