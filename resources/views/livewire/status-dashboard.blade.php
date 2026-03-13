<div class="app" @if($pollInterval > 0) wire:poll.{{ $pollInterval }}s="refresh" @endif>
    @if($view === 'accounts')
        <livewire:account-manager @back="switchView('dashboard')" />
    @endif

    @if($view === 'activity')
        {{-- Activity header --}}
        <div class="header">
            <button class="header__back" wire:click="switchView('dashboard')">
                <svg class="icon-chevron" viewBox="0 0 7 12"><path d="M6 1L1 6l5 5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back
            </button>
            <span class="header__title">Activity</span>
        </div>

        <div class="scroll">
            @if($this->recentDeployments->isEmpty())
                <div class="empty">
                    <svg class="empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <div class="empty__title">No activity yet</div>
                    <div class="empty__sub">Deploy a site to see history here</div>
                </div>
            @else
                @foreach($this->recentDeployments as $deployment)
                    <div class="activity-row" wire:click="navigateToSite({{ $deployment->id }})">
                        <span class="dot dot--sm" style="background:{{ $deployment->statusColor() }}"></span>
                        <div class="activity-row__main">
                            <span class="activity-row__domain">{{ $deployment->site->domain }}</span>
                            @if($deployment->commit_message)
                                <span class="activity-row__commit" title="{{ $deployment->commit_message }}">{{ Str::limit($deployment->commit_message, 40) }}</span>
                            @endif
                        </div>
                        <span class="activity-row__time" title="{{ $deployment->triggered_at->format('M j, Y g:i:s A') }}">{{ $deployment->triggeredAgo() }}</span>
                        <span class="activity-row__server">{{ $deployment->site->server->name }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    @if($view === 'dashboard')
        {{-- Tab bar --}}
        <div class="tabs" tabindex="-1">
            @foreach($this->accounts as $account)
                <div
                    class="tab {{ $activeAccountId === $account->id ? 'tab--active' : '' }}"
                    wire:click="selectAccount({{ $account->id }})"
                >{{ $account->label }}</div>
            @endforeach
            <div class="tab tab--gear" wire:click="switchView('accounts')" title="Manage accounts">
                <svg class="icon-gear" viewBox="0 0 20 20"><path d="M8.586 2.147A2 2 0 0 1 10 1.5a2 2 0 0 1 1.414.647l.529.588A1 1 0 0 0 12.7 3h.8a2 2 0 0 1 2 2v.8a1 1 0 0 0 .265.757l.588.529A2 2 0 0 1 17 8.5a2 2 0 0 1-.647 1.414l-.588.529A1 1 0 0 0 15.5 11.2v.8a2 2 0 0 1-2 2h-.8a1 1 0 0 0-.757.265l-.529.588A2 2 0 0 1 10 15.5a2 2 0 0 1-1.414-.647l-.529-.588A1 1 0 0 0 7.3 14h-.8a2 2 0 0 1-2-2v-.8a1 1 0 0 0-.265-.757l-.588-.529A2 2 0 0 1 3 8.5a2 2 0 0 1 .647-1.414l.588-.529A1 1 0 0 0 4.5 5.8V5a2 2 0 0 1 2-2h.8a1 1 0 0 0 .757-.265l.529-.588ZM10 11.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
            </div>
        </div>

        {{-- Search --}}
        <div class="search-bar">
            <svg class="search-bar__icon" viewBox="0 0 16 16"><circle cx="6.5" cy="6.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.2"/><line x1="10.5" y1="10.5" x2="15" y2="15" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <input class="search-bar__input" type="text" wire:model.live.debounce.200ms="search" placeholder="Filter servers and sites&hellip;">
            @if($search)
                <button class="search-bar__clear" wire:click="$set('search', '')">&times;</button>
            @endif
        </div>

        {{-- Usage warning banner --}}
        @if($this->serversWithHighUsage->isNotEmpty())
            @foreach($this->serversWithHighUsage as $warnServer)
                @php
                    $warnStatus = $warnServer->monitoringStatus();
                    $highest = $warnServer->highestUsageMetric();
                    $isCritical = $warnStatus === 'critical';
                @endphp
                <div
                    class="usage-banner {{ $isCritical ? 'usage-banner--critical' : '' }}"
                    wire:click="toggleServer({{ $warnServer->id }})"
                >
                    <svg class="usage-banner__icon" width="12" height="12" viewBox="0 0 16 16"><path d="M8.7 1.6a.8.8 0 0 0-1.4 0L1.05 13.1a.8.8 0 0 0 .7 1.2h12.5a.8.8 0 0 0 .7-1.2L8.7 1.6Z" fill="currentColor"/><rect x="7.2" y="5.5" width="1.6" height="4.5" rx=".8" fill="#fff"/><circle cx="8" cy="11.8" r=".9" fill="#fff"/></svg>
                    <span class="usage-banner__text"><strong>{{ $warnServer->name }}</strong> &middot; {{ $highest['label'] }} {{ round($highest['value']) }}%</span>
                    <span class="usage-banner__chevron"></span>
                </div>
            @endforeach
        @endif

        {{-- Content --}}
        <div class="scroll">
            <div wire:loading wire:target="refresh, onAccountSaved" class="sync-loading">
                <svg class="icon-sync icon-sync--spin" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z"/></svg>
                <span>Syncing&hellip;</span>
            </div>

            @if($this->accounts->isEmpty())
                <div class="empty">
                    <svg class="empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <div class="empty__title">No Ploi accounts</div>
                    <div class="empty__sub">Add an account to get started</div>
                    <button class="btn btn--primary" wire:click="switchView('accounts')">Add Account</button>
                </div>
            @elseif($this->activeAccount)
                @php $query = strtolower($search); @endphp

                {{-- Pinned sites --}}
                @if(! $search && $this->pinnedSites->isNotEmpty())
                    <div class="section section--pinned">
                        <svg class="section__icon" viewBox="0 0 16 16"><path d="M9.828 1.172a2 2 0 0 1 2.828 0l2.172 2.172a2 2 0 0 1 0 2.828L12 9l-1 4-4-1-3.172-3.172a2 2 0 0 1 0-2.828l6-6z" fill="none" stroke="currentColor" stroke-width="1.2"/><line x1="1" y1="15" x2="6" y2="10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                        Pinned
                    </div>
                    @foreach($this->pinnedSites as $pinnedSite)
                        <div class="pinned-site">
                            <span class="dot dot--sm" style="background:{{ $pinnedSite->statusColor() }}"></span>
                            <span class="pinned-site__domain">{{ $pinnedSite->domain }}</span>
                            <span class="pinned-site__server">{{ $pinnedSite->server->name }}</span>
                            <button class="pinned-site__action" wire:click.stop="openSiteUrl({{ $pinnedSite->id }})" title="Open website">
                                <svg viewBox="0 0 16 16"><path d="M6 3H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1v-3M9 1h6v6M15 1L7 9" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button class="pinned-site__action" wire:click.stop="deploySite({{ $pinnedSite->id }})" wire:confirm="Deploy {{ $pinnedSite->domain }}?" title="Deploy" wire:loading.attr="disabled" wire:target="deploySite({{ $pinnedSite->id }})">
                                <svg wire:loading.remove wire:target="deploySite({{ $pinnedSite->id }})" viewBox="0 0 16 16"><path d="M8 1v10M4 5l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 13h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <svg wire:loading wire:target="deploySite({{ $pinnedSite->id }})" class="icon-deploy--spin" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z" fill="currentColor"/></svg>
                            </button>
                        </div>
                    @endforeach
                @endif

                @foreach($this->projects as $project)
                    @php $filteredServers = $this->filterServers($project->servers); @endphp
                    @if($filteredServers->isNotEmpty())
                        <div class="section">{{ $project->title }}</div>
                        @foreach($filteredServers as $server)
                            @include('livewire.partials.server-row', ['server' => $server, 'searchQuery' => $query])
                        @endforeach
                    @endif
                @endforeach

                @php $filteredUnassigned = $this->filterServers($this->unassignedServers); @endphp
                @if($filteredUnassigned->isNotEmpty())
                    <div class="section">Unassigned</div>
                    @foreach($filteredUnassigned as $server)
                        @include('livewire.partials.server-row', ['server' => $server, 'searchQuery' => $query])
                    @endforeach
                @endif

                @if($this->projects->isEmpty() && $this->unassignedServers->isEmpty())
                    <div class="empty">
                        <svg class="empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="7" rx="1"/><rect x="2" y="14" width="20" height="7" rx="1"/><circle cx="6" cy="6.5" r="1"/><circle cx="6" cy="17.5" r="1"/></svg>
                        <div class="empty__title">No servers found</div>
                        <div class="empty__sub">No servers found for this account</div>
                    </div>
                @elseif($search && ! $this->hasSearchResults)
                    <div class="empty">
                        <div class="empty__title">No results</div>
                        <div class="empty__sub">No servers or sites match "{{ $search }}"</div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div class="foot">
            <span>Last synced: {{ $lastSynced ?? 'never' }}</span>
            <div class="foot__actions">
                <button
                    class="foot__btn foot__activity"
                    wire:click="switchView('activity')"
                    title="Activity"
                >
                    <svg class="icon-activity" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.2"/><polyline points="8 4 8 8 11 10" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button
                    class="foot__btn foot__sync"
                    wire:click="refresh"
                    title="Refresh"
                    wire:loading.attr="disabled"
                    wire:target="refresh"
                >
                    <svg class="icon-sync" wire:loading.class="icon-sync--spin" wire:target="refresh" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z"/></svg>
                </button>
            </div>
        </div>
    @endif
</div>
