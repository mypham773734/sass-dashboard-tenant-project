<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => 1,
            'project_id'  => 1,
            'created_by'  => 1,
            'assignee_id' => null,
            'title'       => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status'      => fake()->randomElement(['todo', 'in_progress', 'in_review', 'done']),
            'priority'    => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'order'       => 0,
            'due_date'    => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'completed_at'=> null,
        ];
    }
}
