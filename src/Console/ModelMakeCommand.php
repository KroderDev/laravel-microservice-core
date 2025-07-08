<?php

namespace Kroderdev\LaravelMicroserviceCore\Console;

use Illuminate\Foundation\Console\ModelMakeCommand as BaseModelMakeCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:model', description: 'Create a new Eloquent model class')]
class ModelMakeCommand extends BaseModelMakeCommand
{
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['remote', null, InputOption::VALUE_NONE, 'Indicates the model should extend the package base model'],
        ]);
    }

    protected function getStub()
    {
        if ($this->option('remote')) {
            return __DIR__.'/stubs/remote-model.stub';
        }

        return parent::getStub();
    }
}
