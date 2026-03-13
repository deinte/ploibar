@php
    $isExpanded = in_array($server->id, $expandedServers);
    $siteCount = $server->sites->count();
    $query = $searchQuery ?? '';
    $monitoringStatus = $server->hasMonitoring() ? $server->monitoringStatus() : null;
    $highestMetric = $monitoringStatus && $monitoringStatus !== 'healthy' ? $server->highestUsageMetric() : null;
@endphp

<div class="row" wire:click="toggleServer({{ $server->id }})">
    <span class="row__disc">
        <svg class="icon-disc {{ $isExpanded ? 'icon-disc--open' : '' }}" viewBox="0 0 8 8"><path d="M2 1l4 3-4 3V1z"/></svg>
    </span>
    <span class="dot" style="background:{{ $server->statusColor() }}"></span>
    <span class="row__name">{{ $server->name }}</span>
    @if($monitoringStatus && $monitoringStatus !== 'healthy')
        @php $iconColor = $monitoringStatus === 'critical' ? '#FF3B30' : '#FF9F0A'; @endphp
        <svg class="monitor-icon" width="12" height="12" viewBox="0 0 16 16" title="{{ $highestMetric['label'] }} at {{ round($highestMetric['value']) }}%"><path d="M8.7 1.6a.8.8 0 0 0-1.4 0L1.05 13.1a.8.8 0 0 0 .7 1.2h12.5a.8.8 0 0 0 .7-1.2L8.7 1.6Z" fill="{{ $iconColor }}"/><rect x="7.2" y="5.5" width="1.6" height="4.5" rx=".8" fill="#fff"/><circle cx="8" cy="11.8" r=".9" fill="#fff"/></svg>
    @endif
    <span class="row__meta">{{ $siteCount }} {{ str('site')->plural($siteCount) }}</span>
</div>

@if($isExpanded)
    <div class="server-detail">
        {{-- IP + SSH + Ploi link --}}
        <div class="detail-copyables">
            <div class="detail-copyable" onclick="copyText(@js($server->ip_address), this)">
                <span class="detail-copyable__label">IP</span>
                <span class="detail-copyable__value">{{ $server->ip_address }}</span>
                <span class="detail-copyable__action">
                    <svg class="icon-copy" viewBox="0 0 16 16"><rect x="5" y="5" width="9" height="9" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M4 11H3.5A1.5 1.5 0 0 1 2 9.5v-7A1.5 1.5 0 0 1 3.5 1h7A1.5 1.5 0 0 1 12 2.5V3" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                </span>
                <span class="detail-copyable__copied">Copied</span>
            </div>
            <div class="detail-copyable__sep"></div>
            <div class="detail-copyable" onclick="copyText(@js('ssh ploi@' . $server->ip_address), this)">
                <span class="detail-copyable__label">SSH</span>
                <span class="detail-copyable__value">{{ 'ploi@' . $server->ip_address }}</span>
                <span class="detail-copyable__action">
                    <svg class="icon-copy" viewBox="0 0 16 16"><rect x="5" y="5" width="9" height="9" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M4 11H3.5A1.5 1.5 0 0 1 2 9.5v-7A1.5 1.5 0 0 1 3.5 1h7A1.5 1.5 0 0 1 12 2.5V3" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                </span>
                <span class="detail-copyable__copied">Copied</span>
            </div>
            <div class="detail-copyable__sep"></div>
            <div class="detail-copyable detail-copyable--link" wire:click.stop="openServerInPloi({{ $server->id }})" title="Open in Ploi">
                <span class="detail-copyable__label">Ploi</span>
                <svg class="icon-external" viewBox="0 0 16 16"><path d="M6 3H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1v-3M9 1h6v6M15 1L7 9" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
        </div>

        {{-- Monitoring --}}
        @if($server->hasMonitoring())
            <div class="server-monitor">
                @php
                    $metrics = [
                        ['label' => 'CPU', 'value' => $server->cpuUsage()],
                        ['label' => 'MEM', 'value' => $server->memoryUsage()],
                        ['label' => 'DISK', 'value' => $server->diskUsage()],
                    ];
                @endphp
                @foreach($metrics as $metric)
                    @if($metric['value'] !== null)
                        @php
                            $val = round($metric['value']);
                            $color = $val > 90 ? 'var(--st-err)' : ($val > 80 ? 'var(--st-warn)' : 'var(--st-run)');
                        @endphp
                        <div class="monitor-metric">
                            <span class="monitor-metric__label">{{ $metric['label'] }}</span>
                            <span class="monitor-metric__pct" style="color:{{ $color }}">{{ $val }}%</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Sites --}}
        @forelse($server->sites as $site)
            @php
                $siteExpanded = in_array($site->id, $expandedSites);
                $matchesQuery = ! $query || str_contains(strtolower($site->domain), $query);
            @endphp
            @if($matchesQuery)
                <div class="site" wire:click.stop="toggleSite({{ $site->id }})">
                    <span class="dot dot--sm" style="background:{{ $site->statusColor() }}"></span>
                    <span class="site__domain">{{ $site->domain }}</span>
                    <span class="site__status">{{ $site->status }}</span>
                    @if($site->is_pinned)
                        <span class="site__pin-badge" title="Pinned">
                            <svg viewBox="0 0 16 16"><path d="M9.828 1.172a2 2 0 0 1 2.828 0l2.172 2.172a2 2 0 0 1 0 2.828L12 9l-1 4-4-1-3.172-3.172a2 2 0 0 1 0-2.828l6-6z" fill="currentColor" stroke="currentColor" stroke-width="0.5"/><line x1="1" y1="15" x2="6" y2="10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                        </span>
                    @endif
                    <button
                        class="site__deploy"
                        wire:click.stop="deploySite({{ $site->id }})"
                        wire:confirm="Deploy {{ $site->domain }} to {{ $server->name }}?"
                        wire:loading.attr="disabled"
                        wire:target="deploySite({{ $site->id }})"
                        title="Deploy"
                    >
                        <svg wire:loading.remove wire:target="deploySite({{ $site->id }})" class="icon-deploy" viewBox="0 0 16 16"><path d="M8 1v10M4 5l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 13h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <svg wire:loading wire:target="deploySite({{ $site->id }})" class="icon-deploy icon-deploy--spin" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z" fill="currentColor"/></svg>
                    </button>
                </div>

                @if($siteExpanded)
                    <div class="site-detail">
                        {{-- Action buttons row --}}
                        <div class="site-actions">
                            <button class="site-actions__btn site-actions__btn--primary" wire:click.stop="deploySite({{ $site->id }})" wire:confirm="Deploy {{ $site->domain }} to {{ $server->name }}?" wire:loading.attr="disabled" wire:target="deploySite({{ $site->id }})">
                                <svg wire:loading.remove wire:target="deploySite({{ $site->id }})" class="icon-deploy" viewBox="0 0 16 16"><path d="M8 1v10M4 5l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 13h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <svg wire:loading wire:target="deploySite({{ $site->id }})" class="icon-deploy icon-deploy--spin" viewBox="0 0 16 16"><path d="M13.65 2.35A7.96 7.96 0 0 0 8 0a8 8 0 1 0 7.74 10h-2.1A6 6 0 1 1 8 2c1.66 0 3.14.69 4.22 1.78L9 7h7V0l-2.35 2.35z" fill="currentColor"/></svg>
                                <span wire:loading.remove wire:target="deploySite({{ $site->id }})">Deploy</span>
                                <span wire:loading wire:target="deploySite({{ $site->id }})">Deploying&hellip;</span>
                            </button>
                            <button class="site-actions__btn" wire:click.stop="openSiteUrl({{ $site->id }})" title="Open website">
                                <svg viewBox="0 0 16 16"><path d="M2 8a6 6 0 1 1 12 0A6 6 0 0 1 2 8zm6-4c-1 0-2.5 1.5-2.5 4S7 12 8 12s2.5-1.5 2.5-4S9 4 8 4zM2.5 8h11" fill="none" stroke="currentColor" stroke-width="1.1"/></svg>
                                Open site
                            </button>
                            <button class="site-actions__btn" wire:click.stop="openInPloi({{ $site->id }})" title="Open in Ploi dashboard">
                                <svg viewBox="0 0 16 16"><path d="M6 3H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1v-3M9 1h6v6M15 1L7 9" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Ploi
                            </button>
                            <button class="site-actions__btn" wire:click.stop="togglePin({{ $site->id }})">
                                <svg viewBox="0 0 16 16"><path d="M9.828 1.172a2 2 0 0 1 2.828 0l2.172 2.172a2 2 0 0 1 0 2.828L12 9l-1 4-4-1-3.172-3.172a2 2 0 0 1 0-2.828l6-6z" fill="{{ $site->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.2"/><line x1="1" y1="15" x2="6" y2="10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                {{ $site->is_pinned ? 'Unpin' : 'Pin' }}
                            </button>
                        </div>

                        {{-- Deploy history --}}
                        @php $recentDeploys = $site->deployments->sortByDesc('triggered_at')->take(3); @endphp
                        @if($recentDeploys->isNotEmpty())
                            <div class="deploy-history">
                                @foreach($recentDeploys as $deploy)
                                    <div class="deploy-row">
                                        <span class="dot dot--xs" style="background:{{ $deploy->statusColor() }}"></span>
                                        @if($deploy->commit_message)
                                            <span class="deploy-row__commit" title="{{ $deploy->commit_message }}">{{ Str::limit($deploy->commit_message, 30) }}</span>
                                        @else
                                            <span class="deploy-row__status">{{ $deploy->status }}</span>
                                        @endif
                                        <span class="deploy-row__time" title="{{ $deploy->triggered_at->format('M j, Y g:i A') }}">{{ $deploy->triggeredAgo() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="deploy-history__empty">No deploy history</div>
                        @endif
                    </div>
                @endif
            @endif
        @empty
            <div class="site site--empty">No sites</div>
        @endforelse
    </div>
@endif
