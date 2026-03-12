<?php

namespace App\Livewire;

use Domain\Account\Models\Account;
use Domain\Ploi\Models\Deployment;
use Domain\Ploi\Models\Site;
use Domain\Sync\Jobs\SyncAccountData;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class StatusDashboard extends Component
{
    public ?int $activeAccountId = null;

    public array $expandedServers = [];

    public array $expandedSites = [];

    public string $view = 'dashboard';

    public bool $syncing = false;

    public ?string $lastSynced = null;

    /** @var array<int, bool> */
    public array $deployingSites = [];

    public function mount(): void
    {
        $this->activeAccountId = Account::where('is_active', true)->first()?->id;
    }

    public function selectAccount(int $accountId): void
    {
        $this->activeAccountId = $accountId;
        $this->expandedServers = [];
        $this->expandedSites = [];
    }

    public function toggleServer(int $serverId): void
    {
        if (in_array($serverId, $this->expandedServers)) {
            $this->expandedServers = array_values(array_diff($this->expandedServers, [$serverId]));

            return;
        }

        $this->expandedServers[] = $serverId;
    }

    public function toggleSite(int $siteId): void
    {
        if (in_array($siteId, $this->expandedSites)) {
            $this->expandedSites = array_values(array_diff($this->expandedSites, [$siteId]));

            return;
        }

        $this->expandedSites[] = $siteId;
    }

    public function deploySite(int $siteId): void
    {
        $site = Site::with('server.account')->findOrFail($siteId);
        $account = $site->server->account;

        $this->deployingSites[$siteId] = true;

        $deployment = Deployment::create([
            'site_id' => $siteId,
            'status' => 'pending',
            'triggered_at' => now(),
            'source' => 'app',
        ]);

        try {
            $ploi = $account->ploiClient();
            $ploi->servers($site->server->ploi_id)
                ->sites($site->ploi_id)
                ->deployment()
                ->deploy();

            $deployment->update(['status' => 'deploying']);
        } catch (\Throwable $exception) {
            $deployment->update(['status' => 'failed', 'completed_at' => now()]);
            session()->flash('error', "Deploy failed: {$exception->getMessage()}");
        }

        unset($this->deployingSites[$siteId]);

        // Auto-expand the site to show the deploy in history
        if (! in_array($siteId, $this->expandedSites)) {
            $this->expandedSites[] = $siteId;
        }
    }

    #[On('account-saved')]
    public function onAccountSaved(): void
    {
        $this->view = 'dashboard';
        $this->activeAccountId = Account::where('is_active', true)->latest()->first()?->id;
        $this->syncAll();
    }

    public function navigateToSite(int $deploymentId): void
    {
        $deployment = Deployment::with('site.server')->find($deploymentId);

        if (! $deployment) {
            return;
        }

        $site = $deployment->site;
        $server = $site->server;

        $this->view = 'dashboard';
        $this->activeAccountId = $server->account_id;

        if (! in_array($server->id, $this->expandedServers)) {
            $this->expandedServers[] = $server->id;
        }

        if (! in_array($site->id, $this->expandedSites)) {
            $this->expandedSites[] = $site->id;
        }
    }

    public function switchView(string $view): void
    {
        $this->view = $view;

        if ($view === 'dashboard') {
            $this->activeAccountId = Account::where('is_active', true)->first()?->id;
        }
    }

    public function refresh(): void
    {
        $this->syncAll();
    }

    #[Computed]
    public function accounts()
    {
        return Account::where('is_active', true)->get();
    }

    #[Computed]
    public function activeAccount(): ?Account
    {
        if (! $this->activeAccountId) {
            return null;
        }

        return Account::find($this->activeAccountId);
    }

    #[Computed]
    public function recentDeployments()
    {
        if (! $this->activeAccountId) {
            return collect();
        }

        return Deployment::with('site.server')
            ->forAccount($this->activeAccountId)
            ->recent(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.status-dashboard');
    }

    private function syncAll(): void
    {
        $this->syncing = true;

        Account::where('is_active', true)
            ->each(fn (Account $account) => SyncAccountData::dispatchSync($account->id));

        $this->lastSynced = 'just now';
        $this->syncing = false;
    }
}
