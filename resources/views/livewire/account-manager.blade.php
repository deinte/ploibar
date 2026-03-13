<div class="app">
    <div class="header">
        <button class="header__back" wire:click="$dispatch('back')">
            <svg class="icon-chevron" viewBox="0 0 7 12"><path d="M6 1L1 6l5 5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back
        </button>
        <span class="header__title">Accounts</span>
    </div>

    <div class="scroll">
        {{-- Existing accounts --}}
        @foreach($this->accounts as $account)
            <div class="acct {{ $editingAccountId === $account->id ? 'acct--editing' : '' }}">
                <div class="acct__info" wire:click="editAccount({{ $account->id }})">
                    <div class="acct__name">{{ $account->label }}</div>
                    <div class="acct__token">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                </div>
                <button
                    class="btn btn--ghost"
                    wire:click="deleteAccount({{ $account->id }})"
                    wire:confirm="Delete '{{ $account->label }}'?"
                >&times;</button>
            </div>
        @endforeach

        {{-- Form --}}
        <div class="form">
            <div class="form__divider">
                {{ $editingAccountId ? 'Edit account' : 'Add account' }}
            </div>

            @if(session()->has('success'))
                <div class="flash flash--ok">{{ session('success') }}</div>
            @endif
            @if(session()->has('error'))
                <div class="flash flash--err">{{ session('error') }}</div>
            @endif

            <div class="field">
                <label class="field__label">Label</label>
                <input type="text" class="field__input" wire:model="label" placeholder="e.g. Personal">
                @error('label') <div class="field__error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label class="field__label">API Token</label>
                <input
                    type="password"
                    class="field__input"
                    wire:model="apiToken"
                    placeholder="{{ $editingAccountId ? 'Leave blank to keep current' : 'Ploi API token' }}"
                >
                @error('apiToken') <div class="field__error">{{ $message }}</div> @enderror
            </div>

            <div class="form__actions">
                @if($editingAccountId)
                    <button class="btn btn--secondary" wire:click="cancelEdit">
                        Cancel
                    </button>
                @endif
                <button class="btn btn--secondary" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">
                    <span wire:loading.remove wire:target="testConnection">Test</span>
                    <span wire:loading wire:target="testConnection">Testing&hellip;</span>
                </button>
                <button class="btn btn--primary" wire:click="saveAccount" wire:loading.attr="disabled" wire:target="saveAccount">
                    <span wire:loading.remove wire:target="saveAccount">{{ $editingAccountId ? 'Update' : 'Save' }}</span>
                    <span wire:loading wire:target="saveAccount">Syncing&hellip;</span>
                </button>
            </div>
        </div>

        {{-- Preferences --}}
        <div class="form">
            <div class="form__divider">Preferences</div>

            <div class="pref-field">
                <div class="pref-field__header">
                    <span class="pref-field__label">Auto-refresh</span>
                    <span class="pref-field__value">
                        @if($pollInterval === 0)
                            Off
                        @elseif($pollInterval < 60)
                            {{ $pollInterval }}s
                        @else
                            {{ $pollInterval / 60 }}m
                        @endif
                    </span>
                </div>
                <div class="segmented" role="radiogroup" aria-label="Polling interval">
                    @foreach([30 => '30s', 60 => '1m', 120 => '2m', 300 => '5m', 0 => 'Off'] as $value => $label)
                        <button
                            class="segmented__btn {{ $pollInterval === $value ? 'segmented__btn--active' : '' }}"
                            wire:click="updatePollInterval({{ $value }})"
                            role="radio"
                            aria-checked="{{ $pollInterval === $value ? 'true' : 'false' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
