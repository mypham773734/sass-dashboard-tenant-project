<?php

use App\Models\Tenant;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Application\User\UseCases\ChangeTenantSelectedUseCase;
use App\Shared\Tenant\TenantContext; 
use App\Application\Tenant\UseCases\GetAllTenantsUseCase; 

new class extends Component
{
    public ?int $tenantId = null;
    public string $errorMessage = '';

    public function mount(): void
    { 
        $this->tenantId = app(TenantContext::class)->getId();
    }

    #[Computed]
    public function tenants()
    {
        $userId = authContext()->getId(); 
        return app(GetAllTenantsUseCase::class)->execute($userId);
    }

    public function switchTenant(int $tenantId): void
    {
        $this->errorMessage = '';

        try {
            app(ChangeTenantSelectedUseCase::class)->execute(Auth::id(), $tenantId);

            app(TenantContext::class)->setId($tenantId);
            $this->tenantId = $tenantId;

            $this->redirect('/admin');
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }
};

?>

<div x-data="{ open: false }" class="relative">

    {{-- Error toast --}}
    @if($this->errorMessage)
    <div
        wire:key="toast-{{ $this->errorMessage }}"
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, 4000)"
        x-show="visible"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="absolute top-12 right-0 z-50 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2 rounded-xl shadow-md whitespace-nowrap"
    >
        <i class="fas fa-exclamation-circle text-red-500 text-xs"></i>
        <span>{{ $this->errorMessage }}</span>
        <button @click="visible = false" class="ml-1 text-red-400 hover:text-red-600 leading-none">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>
    @endif

    {{-- Trigger button --}}
    <button
        @click="open = !open"
        class="flex items-center gap-2 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition rounded-full px-3 py-2 text-sm font-medium"
    >
        {{ $this->tenants->firstWhere('id', $this->tenantId)?->name ?? 'Chọn workspace' }}
        <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-30"
    >
        @forelse($this->tenants as $tenant)
        <button
            wire:click="switchTenant({{ $tenant->id }})"
            @click="open = false"
            @class([
                'flex items-center justify-between w-full px-4 py-2 text-sm transition',
                'text-indigo-600 font-medium bg-indigo-50' => $tenant->id === $this->tenantId,
                'text-slate-700 hover:bg-slate-50'         => $tenant->id !== $this->tenantId,
                'opacity-50'                               => ! $tenant->is_active,
            ])
        >
            <span>{{ $tenant->name }}</span>
            <span class="flex items-center gap-1.5 shrink-0">
                @if(! $tenant->is_active)
                    <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-full leading-tight">Inactive</span>
                @endif
                @if($tenant->id === $this->tenantId)
                    <i class="fas fa-check text-xs text-indigo-500"></i>
                @endif
            </span>
        </button>
        @empty
        <p class="px-4 py-3 text-sm text-slate-400 text-center">Chưa có workspace nào.</p>
        @endforelse
    </div>

</div>
