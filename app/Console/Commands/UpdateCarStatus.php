<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Applicant;
use App\Models\Car;
use Carbon\Carbon;

class UpdateCarStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-car-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $applicants = Applicant::where('expiry_date', '<=', $today)
                               ->where('status', 'Disetujui')
                               ->get();

        foreach ($applicants as $applicant) {
            $car = $applicant->car;

            if ($car) {
                $car->status = 'Available';
                $car->save();
                
            }
        }

        $this->info('Car statuses and applicants updated successfully.');
    }
}
