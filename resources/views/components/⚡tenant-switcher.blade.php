<?php

use Livewire\Component;

new class extends Component
{
    //
};

?>


<div class="w-full max-w-xs">
    <label class="block text-sm font-medium text-slate-700 mb-2">Chọn tenant</label>
    <select
        wire:model="selectedTenant"
        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
        <option value="">-- Chọn tenant --</option>
        <!-- @foreach($tenants as $tenant) -->
            <option value="1">City A</option>
            <option value="2">City B</option>
        <!-- @endforeach -->
    </select>
</div>