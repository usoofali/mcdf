<?php

use App\Models\Member;
use App\Models\State;
use App\Models\Lga;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Create Member'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public string $registration_no = '';
    public string $first_name = '';
    public string $last_name = '';
    public ?string $middle_name = null;
    public ?string $date_of_birth = null;
    public ?string $gender = null;
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?int $state_id = null;
    public ?int $lga_id = null;
    public string $status = 'active';
    public string $registration_date = '';
    public ?string $eligibility_start_date = null;
    public ?string $nin = null;
    public ?string $notes = null;
    public array $lgas = [];

    public function mount(): void
    {
        $this->authorize('create', Member::class);
        $this->registration_date = now()->format('Y-m-d');
    }

    public function updatedStateId($value): void
    {
        $this->lgas = $value ? Lga::where('state_id', $value)->pluck('name', 'id')->toArray() : [];
        $this->lga_id = null;
    }

    public function store(): void
    {
        $validated = $this->validate([
            'registration_no' => ['required', 'string', 'max:255', 'unique:members,registration_no'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'state_id' => ['nullable', 'exists:states,id'],
            'lga_id' => ['nullable', 'exists:lgas,id'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'registration_date' => ['required', 'date'],
            'eligibility_start_date' => ['nullable', 'date'],
            'nin' => ['nullable', 'string', 'size:11', 'unique:members,nin'],
            'notes' => ['nullable', 'string'],
        ]);

        $member = Member::create($validated);

        session()->flash('status', __('Member created successfully.'));

        $this->redirect(route('members.show', $member), navigate: true);
    }

    #[Computed]
    public function states()
    {
        return State::orderBy('name')->pluck('name', 'id');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold sm:text-2xl">{{ __('Create Member') }}</h1>
                </div>
                <flux:button href="{{ route('members.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>

            <form wire:submit="store" class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:input wire:model="registration_no" :label="__('Registration No')" required />
                    <flux:input wire:model="first_name" :label="__('First Name')" required />
                    <flux:input wire:model="last_name" :label="__('Last Name')" required />
                    <flux:input wire:model="middle_name" :label="__('Middle Name')" />
                    <flux:input wire:model="date_of_birth" type="date" :label="__('Date of Birth')" />
                    <flux:select wire:model="gender" :label="__('Gender')">
                        <option value="">{{ __('Select Gender') }}</option>
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </flux:select>
                    <flux:input wire:model="email" type="email" :label="__('Email')" />
                    <flux:input wire:model="phone" :label="__('Phone')" />
                    <flux:textarea wire:model="address" :label="__('Address')" rows="3" />
                    <flux:select wire:model.live="state_id" :label="__('State')">
                        <option value="">{{ __('Select State') }}</option>
                        @foreach($this->states as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="lga_id" :label="__('LGA')" :disabled="empty($lgas)">
                        <option value="">{{ __('Select LGA') }}</option>
                        @foreach($lgas as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="status" :label="__('Status')" required>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                    </flux:select>
                    <flux:input wire:model="registration_date" type="date" :label="__('Registration Date')" required />
                    <flux:input wire:model="eligibility_start_date" type="date" :label="__('Eligibility Start Date')" />
                    <flux:input wire:model="nin" :label="__('NIN')" maxlength="11" placeholder="{{ __('11-digit National ID') }}" />
                    <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
                </div>

                @if(session()->has('success'))
                    <x-alert type="success">{{ session('success') }}</x-alert>
                @endif

                @if(session()->has('error'))
                    <x-alert type="error">{{ session('error') }}</x-alert>
                @endif

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                        {{ __('Create Member') }}
                    </flux:button>
                    <flux:button href="{{ route('members.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
</div>

