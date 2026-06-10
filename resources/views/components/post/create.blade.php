<?php

use Livewire\Component;

new class extends Component
{
    public string $title = '';

    public function save()
    {
        // Save logic here...
    }
};

?>

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Post Title</label>
        <input wire:model="title" type="text" placeholder="Enter post title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <button wire:click="save" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
        {{ __('Save Post') }}
    </button>
</div>
