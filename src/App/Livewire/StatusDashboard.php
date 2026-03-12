<?php

namespace App\Livewire;

use Domain\Account\Models\Account;
use Domain\Ploi\Models\Deployment;
use Domain\Ploi\Models\Server;
use Domain\Ploi\Models\Site;
use Domain\Sync\Jobs\SyncAccountData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class StatusDashboard extends Component
{
    public ?int $activeAccountId = null;

    public array $expandedServers = [];

    public array $expandedSites = [];

    public string $view = 'dashboard';

    public ?string $lastSynced = null;

    public string $search = '';

    /** @var array<int, bool> */
    public array $deployingSites = [];

    public function mount(): void
    {
        $this->activeAccountId = $this->defaultAccountId();
    }

    public function updatedSearch(string $value): void
    {
        $query = strtolower($value);

        if (! $query) {
            return;
        }

        $allServers = $this->projects->flatMap->servers->merge($this->unassignedServers);

        $this->expandedServers = $allServers
            ->filter(fn ($server) => str_contains(strtolower($server->name), $query)
                || $server->sites->contains(fn ($site) => str_contains(strtolower($site->domain), $query)))
            ->pluck('id')
            ->values()
            ->all();
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
        } catch (Throwable $exception) {
            $deployment->update(['status' => 'failed', 'completed_at' => now()]);
            session()->flash('error', "Deploy failed: {$exception->getMessage()}");
        }

        unset($this->deployingSites[$siteId]);

        $this->ensureExpanded($this->expandedSites, $siteId);
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

        $this->ensureExpanded($this->expandedServers, $server->id);
        $this->ensureExpanded($this->expandedSites, $site->id);
    }

    public function togglePin(int $siteId): void
    {
        $site = Site::findOrFail($siteId);
        $site->update(['is_pinned' => ! $site->is_pinned]);
    }

    public function openSiteUrl(int $siteId): void
    {
        $site = Site::findOrFail($siteId);
        $url = str_starts_with($site->domain, 'http') ? $site->domain : "https://{$site->domain}";

        $this->openInBrowser($url);
    }

    public function openInPloi(int $siteId): void
    {
        $site = Site::with('server')->findOrFail($siteId);

        $base = config('services.ploi.url');

        $this->openInBrowser("{$base}/servers/{$site->server->ploi_id}/sites/{$site->ploi_id}");
    }

    public function openServerInPloi(int $serverId): void
    {
        $server = Server::findOrFail($serverId);
        $base = config('services.ploi.url');

        $this->openInBrowser("{$base}/servers/{$server->ploi_id}");
    }

    private function openInBrowser(string $url): void
    {
        $escaped = escapeshellarg($url);
        exec("open {$escaped}");
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
    public function recentDeployments(): SupportCollection
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
    public function pinnedSites(): Collection
    {
        if (! $this->activeAccount) {
            return new Collection;
        }

        return Site::with(['server', 'deployments'])
            ->where('is_pinned', true)
            ->whereHas('server', fn ($q) => $q->where('account_id', $this->activeAccountId))
            ->get();
    }

    #[Computed]
    public function projects(): Collection
    {
        if (! $this->activeAccount) {
            return new Collection;
        }

        return $this->activeAccount->projects()->with(['servers.sites.deployments'])->get();
    }

    #[Computed]
    public function unassignedServers(): Collection
    {
        if (! $this->activeAccount) {
            return new Collection;
        }

        return $this->activeAccount->servers()->with('sites.deployments')->whereNull('project_id')->get();
    }

    #[Computed]
    public function hasSearchResults(): bool
    {
        $query = strtolower($this->search);

        if (! $query) {
            return true;
        }

        $matchesServer = fn ($server) => str_contains(strtolower($server->name), $query)
            || $server->sites->contains(fn ($site) => str_contains(strtolower($site->domain), $query));

        return $this->projects->flatMap->servers->contains($matchesServer)
            || $this->unassignedServers->contains($matchesServer);
    }

    public function filterServers(Collection $servers): Collection
    {
        $query = strtolower($this->search);

        if (! $query) {
            return $servers;
        }

        return $servers->filter(
            fn ($server) => str_contains(strtolower($server->name), $query)
                || $server->sites->contains(fn ($site) => str_contains(strtolower($site->domain), $query))
        );
    }

    public function render(): View
    {
        return view('livewire.status-dashboard');
    }

    private function defaultAccountId(): ?int
    {
        return $this->accounts->first()?->id;
    }

    private function syncAll(): void
    {
        Account::where('is_active', true)
            ->each(fn (Account $account) => SyncAccountData::dispatch($account->id));

        $this->lastSynced = now()->format('H:i');
    }

    private function toggleInArray(array $array, int $id): array
    {
        return in_array($id, $array)
            ? array_values(array_diff($array, [$id]))
            : [...$array, $id];
    }

    private function ensureExpanded(array &$array, int $id): void
    {
        if (! in_array($id, $array)) {
            $array[] = $id;
        }
    }
}
