<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Carbon;
use Faker\Factory as Faker;

class ProjectSeeder extends Seeder
{
    private int $whiteLabelId = 1;
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 5; $i++) {
            $startDate = Carbon::now()->subDays(rand(10, 60));
            $project = Project::create([
                'name' => 'Sample Project ' . $i,
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays(rand(30, 180)),
                'additional_expenses' => $faker->randomFloat(2, 50, 500),
                'details' => $faker->paragraph,
                'white_label_id' => $this->whiteLabelId
            ]);

            $quotations = [];
            $numberOfQuotations = rand(2, 4);

            for ($j = 1; $j <= $numberOfQuotations; $j++) {
                $quotations[] = Quotation::create([
                    'project_id' => $project->id,
                    'company_name' => $faker->company . ' Solutions',
                    'file_path' => 'quotations/sample_quote_' . $project->id . '_' . $j . '.pdf',
                    'amount' => $faker->randomFloat(2, 200, 5000),
                ]);
            }

            // Randomly choose one quotation for the project
            if (!empty($quotations)) {
                $chosenQuotation = $quotations[array_rand($quotations)];
                $project->chosen_quotation_id = $chosenQuotation->id;
                $project->save();
            }
        }
    }
}
