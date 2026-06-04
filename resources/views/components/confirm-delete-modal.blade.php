@props(['title' => 'Delete Confirmation', 'message' => 'Are you sure you want to delete this item? This action cannot be undone.'])

<div x-data="confirmDeleteModal()" @confirm-action.window="handleConfirmAction($event.detail)">
    <!-- Hidden form untuk submit delete -->
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Modal overlay & container -->
    <div x-show="isOpen"
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity"
         x-transition
         @click.self="isOpen = false">

        <!-- Modal box -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="isOpen"
                 class="relative bg-white rounded-lg shadow-xl max-w-md w-full"
                 x-transition>

                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                    <button type="button" @click="isOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-4">
                    <p class="text-gray-600 text-sm">{{ $message }}</p>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 p-4 border-t border-gray-200">
                    <button type="button"
                            @click="isOpen = false"
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
                        Cancel
                    </button>
                    <button type="button"
                            @click="submitDelete()"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-sm">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteModal() {
    return {
        isOpen: false,
        deleteAction: null,

        handleConfirmAction(detail) {
            this.deleteAction = detail.action;
            this.isOpen = true;
        },

        submitDelete() {
            if (this.deleteAction) {
                const form = document.getElementById('delete-form');
                form.action = this.deleteAction;
                form.submit();
            }
        }
    };
}
</script>
