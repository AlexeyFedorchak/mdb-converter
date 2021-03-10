<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test command';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dd(1);
    }
}
