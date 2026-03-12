<?php

namespace App\Livewire;

use Domain\Account\Actions\DeleteAccountWithData;
use Domain\Account\Actions\TestAccountConnection;
use Domain\Account\Models\Account;
use Domain\Sync\Jobs\SyncAccountData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AccountManager extends Component
{
    public string $label = '';

    public string $apiToken = '';

    public ?int $editingAccountId = null;

    public function editAccount(int $accountId): void
    {
        $account = Account::findOrFail($accountId);

        $this->editingAccountId = $account->id;
        $this->label = $account->label;
        $this->apiToken = '';
    }

    public function cancelEdit(): void
    {
        $this->editingAccountId = null;
        $this->reset(['label', 'apiToken']);
        $this->resetValidation();
    }

    public function testConnection(): void
    {
        $this->validate([
            'apiToken' => ['required', 'string', 'min:10'],
        ]);

        $result = app(TestAccountConnection::class)->execute($this->apiToken);

        $result
            ? session()->flash('success', 'Connection successful!')
            : session()->flash('error', 'Connection failed. Check your API token.');
    }

    public function saveAccount(): void
    {
        $tokenRule = $this->editingAccountId
            ? ['nullable', 'string', 'min:10']
            : ['required', 'string', 'min:10'];

        $this->validate([
            'label' => ['required', 'string', 'max:50'],
            'apiToken' => $tokenRule,
        ]);

        if ($this->editingAccountId) {
            $this->updateExistingAccount();

            return;
        }

        $this->createNewAccount();
    }

    public function deleteAccount(int $accountId): void
    {
        $account = Account::findOrFail($accountId);

        app(DeleteAccountWithData::class)->execute($account);

        if ($this->editingAccountId === $accountId) {
            $this->cancelEdit();
        }

        session()->flash('success', 'Account deleted.');
    }

    #[Computed]
    public function accounts(): Collection
    {
        return Account::all();
    }

    public function render(): View
    {
        return view('livewire.account-manager');
    }

    private function updateExistingAccount(): void
    {
        $account = Account::findOrFail($this->editingAccountId);
        $account->label = $this->label;

        if ($this->apiToken !== '') {
            $account->api_token = $this->apiToken;
        }

        $account->save();

        SyncAccountData::dispatchSync($account->id);

        $this->editingAccountId = null;
        $this->reset(['label', 'apiToken']);
        session()->flash('success', 'Account updated!');
    }

    private function createNewAccount(): void
    {
        $account = Account::create([
            'label' => $this->label,
            'api_token' => $this->apiToken,
        ]);

        SyncAccountData::dispatchSync($account->id);

        $this->reset(['label', 'apiToken']);
        $this->dispatch('account-saved');
    }
}
