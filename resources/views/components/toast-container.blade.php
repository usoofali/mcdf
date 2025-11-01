<div
    class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
    x-data="{ toasts: [] }"
    x-on:toast.window="toasts.push($event.detail); setTimeout(() => toasts.shift(), 5000)"
    style="pointer-events: none;"
>
    <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
        <template x-for="(toast, index) in toasts" :key="index">
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => { show = false; setTimeout(() => toasts.splice(index, 1), 300); }, 5000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform translate-y-2"
                class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg shadow-lg ring-1 ring-black ring-opacity-5"
                x-bind:class="toast.bgColor || 'bg-green-500'"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="toast.icon === 'check-circle' || !toast.icon">
                                <flux:icon name="check-circle" class="h-5 w-5 text-white" />
                            </template>
                            <template x-if="toast.icon === 'x-circle'">
                                <flux:icon name="x-circle" class="h-5 w-5 text-white" />
                            </template>
                            <template x-if="toast.icon === 'exclamation-triangle'">
                                <flux:icon name="exclamation-triangle" class="h-5 w-5 text-white" />
                            </template>
                            <template x-if="toast.icon === 'information-circle'">
                                <flux:icon name="information-circle" class="h-5 w-5 text-white" />
                            </template>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-white" x-text="toast.message"></p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                type="button"
                                class="inline-flex rounded-md text-white hover:text-white focus:outline-none focus:ring-2 focus:ring-white"
                                x-on:click="show = false"
                            >
                                <flux:icon name="x-mark" class="h-5 w-5 text-white" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

