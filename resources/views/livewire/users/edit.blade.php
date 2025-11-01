<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Edit User'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public User $user;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public array $roleIds = [];

    public function mount(User $user): void
    {
        $this->authorize('update', $user);
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roleIds = $user->roles->pluck('id')->toArray();
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'roleIds' => ['required', 'array', 'min:1'],
            'roleIds.*' => ['exists:roles,id'],
        ];

        if (!empty($this->password)) {
            $rules['password'] = ['required', 'string', 'min:8'];
            $rules['passwordConfirmation'] = ['required', 'same:password'];
        }

        $validated = $this->validate($rules);

        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'] ?? null)) {
            $this->user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $this->user->roles()->sync($validated['roleIds']);

        session()->flash('success', __('User updated successfully.'));

        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div>
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Edit User') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('Update user information and roles') }}</p>
            </div>
        </div>

        @if(session()->has('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="p-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name')" required autofocus />
                    <flux:input wire:model="email" type="email" :label="__('Email')" required />
                    <flux:input wire:model="password" type="password" :label="__('New Password')" helper-text="{{ __('Leave blank to keep current password') }}" />
                    <flux:input wire:model="passwordConfirmation" type="password" :label="__('Confirm New Password')" />
                </div>

                <div class="mt-6">
                    <flux:field>
                        <flux:label>{{ __('Roles') }}</flux:label>
                        <flux:checkbox.group class="mt-2">
                            @foreach($this->roles as $role)
                                <flux:checkbox wire:model="roleIds" value="{{ $role->id }}" :label="$role->name">
                                    <div class="text-xs text-neutral-500">{{ $role->description }}</div>
                                </flux:checkbox>
                            @endforeach
                        </flux:checkbox.group>
                        @error('roleIds')
                            <flux:error class="mt-1">{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                        {{ __('Update User') }}
                    </flux:button>
                    <flux:button href="{{ route('users.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>

