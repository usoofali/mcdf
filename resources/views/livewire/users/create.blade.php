<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Create User'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public array $roleIds = [];

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirmation' => ['required', 'same:password'],
            'roleIds' => ['required', 'array', 'min:1'],
            'roleIds.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->roles()->sync($validated['roleIds']);

        session()->flash('success', __('User created successfully.'));

        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div>
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ __('Create User') }}</h1>
                <p class="text-xs text-neutral-500 sm:text-sm">{{ __('Add a new system user') }}</p>
            </div>
        </div>


        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="p-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name')" required autofocus />
                    <flux:input wire:model="email" type="email" :label="__('Email')" required />
                    <flux:input wire:model="password" type="password" :label="__('Password')" viewable required />
                    <flux:input wire:model="passwordConfirmation" type="password" :label="__('Confirm Password')" viewable required />
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
                        {{ __('Create User') }}
                    </flux:button>
                    <flux:button href="{{ route('users.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-alert variant="error" :timeout="5000">
            {{ session('error') }}
        </x-alert>
    </div>
    @endif
    @if (session()->has('success'))
        <div class="fixed bottom-4 right-4 z-50">
        <x-alert variant="success" :timeout="5000">
            {{ session('success') }}
        </x-alert>
    </div>
    @endif
</div>

