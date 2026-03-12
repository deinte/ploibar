@php
    $isExpanded = in_array($server->id, $expandedServers);
    $siteCount = $server->sites->count();
@endphp

<div class="row" wire:click="toggleServer({{ $server->id }})">
    <span class="row__disc">
        <svg class="icon-disc {{ $isExpanded ? 'icon-disc--open' : '' }}" viewBox="0 0 8 8"><path d="M2 1l4 3-4 3V1z"/></svg>
    </span>
    <span class="dot" style="background:{{ $server->statusColor() }}"></span>
    <span class="row__name">{{ $server->name }}</span>
    <span class="row__meta">{{ $siteCount }} {{ str('site')->plural($siteCount) }}</span>
</div>

@if($isExpanded)
    <div class="server-detail">
        {{-- IP + SSH --}}
        <div class="detail-copyables">
            <div
                class="detail-copyable"
                onclick="navigator.clipboard.writeText('{{ $server->ip_address }}').then(() => { this.classList.add('detail-copyable--copied'); setTimeout(() => this.classList.remove('detail-copyable--copied'), 1200) })"
            >
                <span class="detail-copyable__label">IP</span>
                <span class="detail-copyable__value">{{ $server->ip_address }}</span>
                <span class="detail-copyable__action">
                    <svg class="icon-copy" viewBox="0 0 16 16"><rect x="5" y="5" width="9" height="9" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M4 11H3.5A1.5 1.5 0 0 1 2 9.5v-7A1.5 1.5 0 0 1 3.5 1h7A1.5 1.5 0 0 1 12 2.5V3" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                </span>
                <span class="detail-copyable__copied">Copied</span>
            </div>
            <div class="detail-copyable__sep"></div>
            <div
                class="detail-copyable"
                onclick="navigator.clipboard.writeText('ssh ploi@{{ $server->ip_address }}').then(() => { this.classList.add('detail-copyable--copied'); setTimeout(() => this.classList.remove('detail-copyable--copied'), 1200) })"
            >
                <span class="detail-copyable__label">SSH</span>
                <span class="detail-copyable__value">ploi@{{ $server->ip_address }}</span>
                <span class="detail-copyable__action">
                    <svg class="icon-copy" viewBox="0 0 16 16"><rect x="5" y="5" width="9" height="9" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M4 11H3.5A1.5 1.5 0 0 1 2 9.5v-7A1.5 1.5 0 0 1 3.5 1h7A1.5 1.5 0 0 1 12 2.5V3" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                </span>
                <span class="detail-copyable__copied">Copied</span>
            </div>
        </div>

        {{-- Sites --}}
        @forelse($server->sites as $site)
            @php $siteExpanded = in_array($site->id, $expandedSites); @endphp
            <div class="site" wire:click.stop="toggleSite({{ $site->id }})">
                <span class="dot dot--sm" style="background:{{ $site->statusColor() }}"></span>
                <span class="site__domain">{{ $site->domain }}</span>
                <span class="site__status">{{ $site->status }}</span>
                <button
                    class="site__deploy"
                    wire:click.stop="deploySite({{ $site->id }})"
                    wire:loading.attr="disabled"
                    wire:target="deploySite({{ $site->id }})"
                    title="Deploy {{ $site->domain }}"
                >
                    <svg wire:loading.remove wire:target="deploySite({{ $site->id }})" class="icon-deploy" viewBox="0 0 16 16"><path d="M8 1v10M4 5l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 13h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <svg wire:loading wire:target="deploySite({{ $site->id }})" class="icon-deploy icon-deploy--spin" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z" fill="currentColor"/></svg>
                </button>
            </div>

            @if($siteExpanded)
                <div class="site-detail">
                    <button
                        class="deploy-btn"
                        wire:click.stop="deploySite({{ $site->id }})"
                        wire:loading.attr="disabled"
                        wire:target="deploySite({{ $site->id }})"
                    >
                        <svg wire:loading.remove wire:target="deploySite({{ $site->id }})" class="icon-deploy" viewBox="0 0 16 16"><path d="M8 1v10M4 5l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 13h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <svg wire:loading wire:target="deploySite({{ $site->id }})" class="icon-deploy icon-deploy--spin" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z" fill="currentColor"/></svg>
                        <span wire:loading.remove wire:target="deploySite({{ $site->id }})">Deploy</span>
                        <span wire:loading wire:target="deploySite({{ $site->id }})">Deploying&hellip;</span>
                    </button>

                    @php $recentDeploys = $site->deployments()->orderByDesc('triggered_at')->limit(3)->get(); @endphp
                    @if($recentDeploys->isNotEmpty())
                        <div class="deploy-history">
                            @foreach($recentDeploys as $deploy)
                                <div class="deploy-row">
                                    <span class="dot dot--xs" style="background:{{ $deploy->statusColor() }}"></span>
                                    <span class="deploy-row__status">{{ $deploy->status }}</span>
                                    <span class="deploy-row__time" title="{{ $deploy->triggered_at->format('M j, Y g:i A') }}">{{ $deploy->triggeredAgo() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="deploy-history__empty">No deploy history</div>
                    @endif
                </div>
            @endif
        @empty
            <div class="site site--empty">No sites</div>
        @endforelse
    </div>
@endif
