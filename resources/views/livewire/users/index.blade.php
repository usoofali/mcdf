<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'User Management'])] class extends VoltComponent
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public ?int $selectedRole = null;
    public bool $showDeleteModal = false;
    public ?int $userToDelete = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->selectedRole, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('roles.id', $this->selectedRole);
                });
            })
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedRole(): void
    {
        $this->resetPage();
    }

    public function openDeleteModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('delete', $user);
        $this->userToDelete = $userId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->userToDelete = null;
    }

    public function delete(): void
    {
        if (!$this->userToDelete) {
            return;
        }

        $user = User::findOrFail($this->userToDelete);
        $this->authorize('delete', $user);

        try {
            $user->delete();
            $this->closeDeleteModal();
            session()->flash('success', __('User deleted successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to delete user.'));
            $this->closeDeleteModal();
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('User Management') }}</h1>
                <p class="text-sm text-neutral-500">{{ __('Manage system users and roles') }}</p>
            </div>
            @can('create', \App\Models\User::class)
                <flux:button href="{{ route('users.create') }}" variant="primary" wire:navigate>
                    {{ __('Create User') }}
                </flux:button>
            @endcan
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        @if(session()->has('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        <!-- Filters -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="p-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by name or email...') }}" />
                    <flux:select wire:model.live="selectedRole" :label="__('Filter by Role')">
                        <option value="">{{ __('All Roles') }}</option>
                        @foreach($this->roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Roles') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">{{ __('Created') }}</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->users as $user)
                            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-200 text-sm font-semibold dark:bg-neutral-700">
                                            {{ $user->initials() }}
                                        </div>
                                        <div class="font-medium">{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse($user->roles as $role)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                {{ $role->slug === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                                {{ $role->slug === 'finance' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                                {{ $role->slug === 'health' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                {{ $role->slug === 'member' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}
                                            ">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-neutral-500">{{ __('No roles') }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-neutral-500">{{ $user->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('update', $user)
                                            <flux:button href="{{ route('users.edit', $user) }}" variant="ghost" size="sm" wire:navigate>
                                                {{ __('Edit') }}
                                            </flux:button>
                                        @endcan
                                        @can('delete', $user)
                                            <flux:button wire:click="openDeleteModal({{ $user->id }})" variant="ghost" size="sm">
                                                {{ __('Delete') }}
                                            </flux:button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-neutral-500">
                                    {{ __('No users found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->users->hasPages())
                <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    {{ $this->users->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($userToDelete)
        @php
            $userToDeleteObj = User::find($userToDelete);
        @endphp
        <flux:modal name="delete-user-modal" :show="$showDeleteModal" focusable class="max-w-lg">
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Delete User') }}</flux:heading>
                    <flux:subheading>{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}</flux:subheading>
                </div>

                @if($userToDeleteObj)
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-900">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-200 text-sm font-semibold dark:bg-neutral-700">
                                {{ $userToDeleteObj->initials() }}
                            </div>
                            <div>
                                <div class="font-semibold">{{ $userToDeleteObj->name }}</div>
                                <div class="text-sm text-neutral-500">{{ $userToDeleteObj->email }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button type="button" wire:click="closeDeleteModal" variant="ghost">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="delete" variant="danger">
                        {{ __('Delete User') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

