<?php

namespace App\Jobs;

use App\Models\Table;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        shell_exec('mdb-export ' . env('USER_PATH') . 'public/data.mdb ' . $this->table->name . ' > ' . env('USER_PATH') . 'public/' . $this->table->name . '.csv');

        $this->table->filename = $this->table->name . '.csv';
        $this->table->save();

        Schema::dropIfExists($this->table->name);
        if (!Schema::hasTable($this->table->name)) {
            $firstRow = $this->getRow($this->table);
            $columns = [];

            $columnsNames = [];
            foreach ($firstRow[0] as $column) {
                $columns[] = $column . ' LONGTEXT NULL';
                $columnsNames[] = $column;
            }

            DB::statement('
                    CREATE TABLE '. $this->table->name . ' (
                        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        '. implode(',', $columns) .',
                        timing TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ');
            DB::statement('ALTER TABLE ' . $this->table->name . ' CONVERT TO CHARACTER SET utf8');

            $start = 1;
            $data = $this->getRow($this->table, $start);
            while(!empty($data[0])) {
                foreach ($data[0] as $key => $item) {
                    $data[0][$key] = str_replace('"', '\"', $item);
                }

                $rowData = '"' . implode('","', $data[0]) . '"';

                if (strlen($rowData) <= 3) {
                    $start++;
                    $data = $this->getRow($this->table, $start);
                    continue;
                }

                try {
                    DB::statement('
                    INSERT ' . $this->table->name . '(' . implode(',', $columnsNames) . ')
                    VALUES (' . $rowData . ');
                ');
                } catch (\Exception $exception) {
                    dd($exception->getMessage(), '
                    INSERT ' . $this->table->name . '(' . implode(',', $columnsNames) . ')
                    VALUES (' . $rowData . ');
                    ', $rowData);
                }


                $start++;
                $data = $this->getRow($this->table, $start);
            }
        }
    }

    private function getRow(Table $table, $start = 0, $offset = 1)
    {
        $tableHandler = fopen(env('USER_PATH') . 'public/' . $this->table->name . '.csv', 'r');

        $iteration = 0;
        $steps = 1;
        $data = [];

        while($row = fgetcsv($tableHandler, 5000)) {
            if ($iteration >= $start && $steps <= $offset) {
                $data[] = $row;

                $steps++;
            }

            $iteration++;
        }

        fclose($tableHandler);
        return $data;
    }
}
