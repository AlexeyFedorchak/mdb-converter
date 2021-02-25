<?php

namespace App\Jobs;

use App\Models\Table;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchTable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $table;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        shell_exec('mdb-export public/data.mdb ' . $this->table->name . ' > ' . 'public/' . $this->table->name . '.csv');

        $this->table->filename = $this->table->name . '.csv';
        $this->table->save();
    }
}
