<?php

use App\Models\Contribution;
use App\Models\ContributionPlan;
use App\Models\Member;
use App\Services\ContributionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component as VoltComponent;

new #[Layout('components.layouts.app', ['title' => 'Submit Contribution'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public int $contribution_plan_id = 0;
    public string $amount = '';
    public string $payment_method = 'cash';
    public ?string $payment_ref = null;
    public string $payment_date = '';
    public ?TemporaryUploadedFile $receipt = null;
    public ?string $receipt_notes = null;

    public function mount(): void
    {
        $this->authorize('submit', Contribution::class);
        $this->payment_date = now()->format('Y-m-d');

        // Get member associated with authenticated user
        // For MVP, assuming user has a member relationship
        // TODO: Implement proper member-user relationship
    }

    #[Computed]
    public function plans()
    {
        return ContributionPlan::where('is_active', true)->orderBy('name')->get();
    }

    public function submit(ContributionService $service): void
    {
        $validated = $this->validate([
            'contribution_plan_id' => ['required', 'exists:contribution_plans,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'payment_ref' => ['nullable', 'string', 'max:100', 'unique:contributions,payment_ref'],
            'payment_date' => ['required', 'date'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'receipt_notes' => ['nullable', 'string'],
        ]);

        try {
            // TODO: Get member from authenticated user
            // For now, this is a placeholder - need to implement member-user relationship
            session()->flash('error', __('Member relationship not yet implemented. Please contact administrator.'));

            // Once implemented:
            // $member = Auth::user()->member;
            // $contribution = $service->submit($member, $validated, $this->receipt);
            // session()->flash('status', __('Contribution submitted successfully.'));
            // $this->redirect(route('members.show', $member), navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ __('Submit Contribution') }}</h1>
                <flux:button href="{{ route('contributions.index') }}" variant="ghost" wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>

            <form wire:submit="submit" class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="grid gap-6 md:grid-cols-2">
                    <flux:select wire:model="contribution_plan_id" :label="__('Contribution Plan')" required>
                        <option value="0">{{ __('Select Plan') }}</option>
                        @foreach($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} @if($plan->amount)(₦{{ number_format($plan->amount, 2) }})@endif</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="amount" type="number" step="0.01" :label="__('Amount')" required />
                    <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="transfer">{{ __('Transfer') }}</option>
                    </flux:select>
                    <flux:input wire:model="payment_ref" :label="__('Payment Reference')" placeholder="{{ __('Optional') }}" />
                    <flux:input wire:model="payment_date" type="date" :label="__('Payment Date')" required />
                    <div class="md:col-span-2">
                        <flux:input wire:model="receipt" type="file" accept="image/*,.pdf" :label="__('Receipt')" required />
                        <flux:text class="text-xs text-neutral-500">{{ __('Required: Max 5MB - JPG, PNG, or PDF') }}</flux:text>
                    </div>
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="receipt_notes" :label="__('Notes')" rows="3" />
                    </div>
                </div>

                @if(session()->has('success'))
                    <x-alert type="success">{{ session('success') }}</x-alert>
                @endif

                @if(session()->has('error'))
                    <x-alert type="error">{{ session('error') }}</x-alert>
                @endif

                <div class="mt-6 flex items-center gap-4">
                    <flux:button type="submit" variant="primary">
                        {{ __('Submit Contribution') }}
                    </flux:button>
                    <flux:button href="{{ route('contributions.index') }}" variant="ghost" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
</div>

