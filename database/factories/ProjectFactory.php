<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => 1,
            'onwer_id'    => 1,
            'name'        => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'status'      => 'active',
        ];
    }
}
