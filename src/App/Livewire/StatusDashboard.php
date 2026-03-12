<?php

namespace App\Livewire;

use Domain\Account\Models\Account;
use Domain\Ploi\Models\Deployment;
use Domain\Ploi\Models\Site;
use Domain\Sync\Jobs\SyncAccountData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class StatusDashboard extends Component
{
    public ?int $activeAccountId = null;

    public array $expandedServers = [];

    public array $expandedSites = [];

    public string $view = 'dashboard';

    public ?string $lastSynced = null;

    /** @var array<int, bool> */
    public array $deployingSites = [];

    public function mount(): void
    {
        $this->activeAccountId = $this->defaultAccountId();
    }

    public function selectAccount(int $accountId): void
    {
        $this->activeAccountId = $accountId;
        $this->expandedServers = [];
        $this->expandedSites = [];
    }

    public function toggleServer(int $serverId): void
    {
        $this->expandedServers = $this->toggleInArray($this->expandedServers, $serverId);
    }

    public function toggleSite(int $siteId): void
    {
        $this->expandedSites = $this->toggleInArray($this->expandedSites, $siteId);
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
        $this->activeAccountId = $this->defaultAccountId();
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
            $this->activeAccountId = $this->defaultAccountId();
        }
    }

    public function refresh(): void
    {
        $this->syncAll();
    }

    #[Computed]
    public function accounts(): Collection
    {
        return Account::where('is_active', true)->get();
    }

    #[Computed]
    public function activeAccount(): ?Account
    {
        if (! $this->activeAccountId) {
            return null;
        }

        return $this->accounts->firstWhere('id', $this->activeAccountId);
    }

    #[Computed]
    public function recentDeployments(): \Illuminate\Support\Collection
    {
        if (! $this->activeAccountId) {
            return collect();
        }

        return Deployment::with('site.server')
            ->forAccount($this->activeAccountId)
            ->recent(20)
            ->get();
    }

    #[Computed]
    public function projects(): Collection
    {
        if (! $this->activeAccount) {
            return new Collection;
        }

        return $this->activeAccount->projects()->with(['servers.sites'])->get();
    }

    #[Computed]
    public function unassignedServers(): Collection
    {
        if (! $this->activeAccount) {
            return new Collection;
        }

        return $this->activeAccount->servers()->with('sites')->whereNull('project_id')->get();
    }

    public function render(): View
    {
        return view('livewire.status-dashboard');
    }

    private function defaultAccountId(): ?int
    {
        return Account::where('is_active', true)->first()?->id;
    }

    private function syncAll(): void
    {
        Account::where('is_active', true)
            ->each(fn (Account $account) => SyncAccountData::dispatchSync($account->id));

        $this->lastSynced = now()->format('g:i A');
    }

    private function toggleInArray(array $array, int $id): array
    {
        return in_array($id, $array)
            ? array_values(array_diff($array, [$id]))
            : [...$array, $id];
    }
}
