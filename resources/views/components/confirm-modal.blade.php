@props([
    'id' => 'confirm-modal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'isDangerous' => false,
])

<div
    x-data="{
        open: false,
        formAction: '',
        init() {
            $watch('open', value => {
                if (!value) this.formAction = '';
            });
        }
    }"
    x-init="init()"
    x-on:confirm-action.window="
        if ($event.detail.id === '{{ $id }}') {
            open = true;
            formAction = $event.detail.action;
        }
    "
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;">

    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="open = false"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity">
    </div>

    <!-- Modal -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative mx-auto w-full max-w-md transform rounded-lg bg-white p-6 shadow-xl transition-all">

        <!-- Icon -->
        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full {{ $isDangerous ? 'bg-red-100' : 'bg-blue-100' }}">
            <svg class="h-6 w-6 {{ $isDangerous ? 'text-red-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($isDangerous)
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6.343 3.665c-.933 1.47-1.299 3.407-.576 5.205.51 1.411 1.563 2.556 2.853 3.144 1.543.771 3.328.771 4.871 0 1.29-.588 2.343-1.733 2.853-3.144.723-1.798.357-3.735-.576-5.205m0 0a6.09 6.09 0 00-6.694 0M3 13.5a3 3 0 100 6h12a3 3 0 100-6" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                @endif
            </svg>
        </div>

        <!-- Content -->
        <div class="mt-3 text-center sm:mt-5">
            <h3 class="text-lg font-medium text-gray-900">
                {{ $title }}
            </h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500">
                    {{ $message }}
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 grid grid-cols-2 gap-3 sm:mt-6">
            <button
                type="button"
                x-on:click="open = false"
                class="inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                {{ $cancelText }}
            </button>

            <form :action="formAction" method="POST" style="display: contents;">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors {{ $isDangerous ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700' }}">
                    {{ $confirmText }}
                </button>
            </form>
        </div>
    </div>
</div>
