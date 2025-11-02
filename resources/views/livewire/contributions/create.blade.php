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

new #[Layout('components.layouts.app', ['title' => 'Record Contribution'])] class extends VoltComponent
{
    use AuthorizesRequests;

    public ?int $member_id = null;
    public int $contribution_plan_id = 0;
    public string $amount = '';
    public string $payment_method = 'cash';
    public ?string $payment_ref = null;
    public string $payment_date = '';
    public ?TemporaryUploadedFile $receipt = null;
    public ?string $receipt_notes = null;
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('create', Contribution::class);
        $this->payment_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function members()
    {
        if (empty($this->search)) {
            return collect();
        }

        return Member::where('first_name', 'like', '%' . $this->search . '%')
            ->orWhere('last_name', 'like', '%' . $this->search . '%')
            ->orWhere('registration_no', 'like', '%' . $this->search . '%')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function plans()
    {
        return ContributionPlan::where('is_active', true)->orderBy('name')->get();
    }

    public function store(ContributionService $service): void
    {
        $validated = $this->validate([
            'member_id' => ['required', 'exists:members,id'],
            'contribution_plan_id' => ['required', 'exists:contribution_plans,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'payment_ref' => ['nullable', 'string', 'max:100', 'unique:contributions,payment_ref'],
            'payment_date' => ['required', 'date'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'receipt_notes' => ['nullable', 'string'],
        ]);

        try {
            $member = Member::findOrFail($validated['member_id']);
            $contribution = $service->record($member, Auth::user(), $validated, $this->receipt);

            session()->flash('status', __('Contribution recorded successfully.'));
            $this->redirect(route('contributions.index'), navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function updatedSearch(): void
    {
        $this->member_id = null;
    }

    public function selectMember(int $id): void
    {
        $this->member_id = $id;
        $this->search = Member::findOrFail($id)->full_name;
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold sm:text-2xl">{{ __('Record Contribution') }}</h1>
                </div>
                <flux:button href="{{ route('contributions.index') }}" variant="ghost" class="w-full sm:w-auto" wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>

            <form wire:submit="store" class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search Member')" placeholder="{{ __('Name or Registration No') }}" />
                        @if(!empty($search) && empty($member_id))
                            <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                                @forelse($this->members as $member)
                                    <button type="button" wire:click="selectMember({{ $member->id }})" class="w-full px-4 py-2 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-700">
                                        {{ $member->full_name }} ({{ $member->registration_no }})
                                    </button>
                                @empty
                                    <div class="px-4 py-2 text-sm text-neutral-500">{{ __('No members found') }}</div>
                                @endforelse
                            </div>
                        @endif
                        @if($member_id)
                            <div class="mt-2 rounded-lg bg-green-100 p-2 text-sm text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ __('Selected:') }} {{ Member::find($member_id)?->full_name }}
                            </div>
                        @endif
                        @error('member_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

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
                        <flux:input wire:model="receipt" type="file" accept="image/*,.pdf" :label="__('Receipt')" />
                        <flux:text class="text-xs text-neutral-500">{{ __('Optional: Max 5MB - JPG, PNG, or PDF') }}</flux:text>
                    </div>
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="receipt_notes" :label="__('Notes')" rows="3" />
                    </div>
                </div>


                <div class="mt-6 flex items-center gap-4">
                    <flux:button type="submit" variant="primary">
                        {{ __('Record Contribution') }}
                    </flux:button>
                    <flux:button href="{{ route('contributions.index') }}" variant="ghost" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
        <!-- Flash Message -->
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

