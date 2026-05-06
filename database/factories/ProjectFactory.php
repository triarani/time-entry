<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<Project> */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'company_id'  => Company::factory(),
            'name'        => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
        ];
    }
}
