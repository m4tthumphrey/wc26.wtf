<?php

namespace App\Console\Commands;

use Illuminate\Cache\Repository;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class CacheSomethingCommand extends Command
{
    protected $name = 'cache:something';

    public function handle(Repository $repository)
    {
        $repository->put($this->argument('key'), $this->argument('value'));
    }

    protected function getArguments()
    {
        return [
            ['key', InputArgument::REQUIRED, 'The name of the key.'],
            ['value', InputArgument::REQUIRED, 'The value to story.'],
        ];
    }
}
