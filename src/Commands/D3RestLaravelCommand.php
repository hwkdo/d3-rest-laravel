<?php

namespace Hwkdo\D3RestLaravel\Commands;

use Illuminate\Console\Command;

class D3RestLaravelCommand extends Command
{
    public $signature = 'd3-rest-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
