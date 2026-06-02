<?php

use App\Models\Tenant;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new class extends Component
{
    public $tenantId;
    public $tenants;

    public function mount()
    {
        $this->tenantId = session('current_tenant_id');
        $this->tenants = Tenant::all();

    }

    public function switchTenant($tenantId)
    {
        $user = Auth::user();

        // Bắt buộc kiểm tra bảo mật
        // Đảm bảo User thuộc tenant này để tránh hack ID
        $isBelong = $user->tenants()->where('tenant_id', $tenantId)->exists();

        if ($isBelong) {
            // Set session 
            session()->put('current_tenant_id', $tenantId);
            session()->flash('message', 'Chuyển đổi workspace thành công');

            $this->tenantId = $tenantId;
        } else {
            session()->flash('message', 'Bạn không có quyển truy cập vào workspace này');
            $this->tenantId = session('current_tenant_id');
        }
    }
};

?>

<div>
    <div class="relative">
        <button id="dropdownMenuBtn" class="flex items-center gap-2 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition rounded-full px-3 py-2 text-sm font-medium">
            {{ optional(Tenant::find($this->tenantId))->name ?? 'Chưa có tenant' }}
            <i class="fas fa-chevron-down text-xs"></i>
        </button>
        @if (isset($this->tenants))
        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-30">
            @foreach ($this->tenants as $tenant)
            <button wire:click="switchTenant({{ $tenant->id }})" class="block w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">{{ $tenant->name }}</button>
            @endforeach
        </div>
        @endif
    </div>
</div>