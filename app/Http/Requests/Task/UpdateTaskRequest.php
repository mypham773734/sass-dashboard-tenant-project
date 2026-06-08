<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'in:todo,in_progress,in_review,done'],
            'priority'    => ['required', 'in:low,medium,high,critical'],
            'due_date'    => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
