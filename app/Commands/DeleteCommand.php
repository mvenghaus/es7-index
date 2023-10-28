<?php

namespace App\Commands;

use Elasticsearch\ClientBuilder;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\multiselect;

class DeleteCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'delete';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete indices by prompt selection';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = ClientBuilder::create()
            ->setHosts([env('ELASTICSEARCH_HOST')])
            ->build();

        $indices = [];
        foreach ($client->cat()->indices() as $indexData) {
            $indices[$indexData['index']] = $indexData['index'];
        }

        $selectedIndices = multisearch(
            label: 'Search for an index:',
            options: fn($value) => array_filter(
                $indices,
                fn($index) => str_contains($index, $value)
            ),
            scroll: 10,
        );

        if (count($selectedIndices) > 0) {
            $confirmed = confirm('Do you really want to delete?');
            if ($confirmed) {
                foreach ($selectedIndices as $index) {
                    $client->indices()->delete(['index' => $index]);
                }

                $this->output->info('Indices deleted!');
            }
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
