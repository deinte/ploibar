<?php

namespace Domain\Sync\Jobs;

use Domain\Account\Models\Account;
use Domain\Ploi\Models\Deployment;
use Domain\Ploi\Models\Project;
use Domain\Ploi\Models\Server;
use Domain\Ploi\Models\Site;
use Domain\Sync\Actions\DetectStatusChanges;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Ploi\Ploi;
use Throwable;

class SyncAccountData implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $accountId,
    ) {}

    public function handle(DetectStatusChanges $detectStatusChanges): void
    {
        $account = Account::find($this->accountId);

        if (! $account || ! $account->is_active) {
            return;
        }

        $ploi = $account->ploiClient();

        $oldServerStatuses = $account->servers()->pluck('status', 'id')->toArray();
        $oldSiteStatuses = Site::whereIn('server_id', array_keys($oldServerStatuses))->pluck('status', 'id')->toArray();

        $this->syncServers($account, $ploi);
        $this->syncProjects($account, $ploi);
        $this->cleanupStaleRecords($account);

        $detectStatusChanges->execute($account, $oldServerStatuses, $oldSiteStatuses);
    }

    private function syncServers(Account $account, Ploi $ploi): void
    {
        try {
            $response = $ploi->servers()->get();
            $servers = collect($response->getData() ?? []);
        } catch (Throwable $exception) {
            Log::warning('SyncAccountData: failed to fetch servers', [
                'account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $syncedIds = [];

        foreach ($servers as $serverData) {
            $server = Server::updateOrCreate(
                ['account_id' => $account->id, 'ploi_id' => $serverData->id],
                [
                    'name' => $serverData->name,
                    'ip_address' => $serverData->ip_address ?? null,
                    'status' => $serverData->status ?? 'unknown',
                    'type' => $serverData->type ?? null,
                    'php_version' => $serverData->php_version ?? null,
                ],
            );

            $syncedIds[] = $server->id;

            $this->syncSitesForServer($server, $ploi);
        }

        $account->servers()->whereNotIn('id', $syncedIds)->delete();
    }

    private function syncSitesForServer(Server $server, Ploi $ploi): void
    {
        try {
            $response = $ploi->servers($server->ploi_id)->sites()->get();
            $sites = collect($response->getData() ?? []);
        } catch (Throwable $exception) {
            Log::warning('SyncAccountData: failed to fetch sites for server', [
                'account_id' => $server->account_id,
                'server_id' => $server->id,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $existingSites = Site::where('server_id', $server->id)->pluck('last_deploy_at', 'ploi_id');

        $syncedIds = [];

        foreach ($sites as $siteData) {
            $oldDeployAt = $existingSites->get($siteData->id);
            $newDeployAt = $siteData->last_deploy_at ?? null;

            $site = Site::updateOrCreate(
                ['server_id' => $server->id, 'ploi_id' => $siteData->id],
                [
                    'domain' => $siteData->domain ?? $siteData->root_domain ?? 'unknown',
                    'status' => $siteData->status ?? 'unknown',
                    'project_type' => $siteData->project_type ?? null,
                    'last_deploy_at' => $newDeployAt,
                ],
            );

            if ($newDeployAt && $newDeployAt !== $oldDeployAt) {
                $commitMessage = $this->fetchLatestCommitMessage($ploi, $server->ploi_id, $siteData->id);

                Deployment::updateOrCreate(
                    ['site_id' => $site->id, 'triggered_at' => $newDeployAt],
                    [
                        'status' => 'completed',
                        'completed_at' => now(),
                        'source' => 'api',
                        'commit_message' => $commitMessage,
                    ],
                );
            }

            $syncedIds[] = $site->id;
        }

        $server->sites()->whereNotIn('id', $syncedIds)->delete();
    }

    private function syncProjects(Account $account, Ploi $ploi): void
    {
        try {
            $response = $ploi->projects()->get();
            $projects = collect($response->getData() ?? []);
        } catch (Throwable $exception) {
            Log::warning('SyncAccountData: failed to fetch projects', [
                'account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $syncedIds = [];

        foreach ($projects as $projectData) {
            $project = Project::updateOrCreate(
                ['account_id' => $account->id, 'ploi_id' => $projectData->id],
                ['title' => $projectData->title ?? $projectData->name ?? 'Untitled'],
            );

            $syncedIds[] = $project->id;

            if (isset($projectData->servers) && is_array($projectData->servers)) {
                $serverPloiIds = collect($projectData->servers)->pluck('id');
                $account->servers()
                    ->whereIn('ploi_id', $serverPloiIds)
                    ->update(['project_id' => $project->id]);
            }
        }

        $account->projects()->whereNotIn('id', $syncedIds)->delete();
    }

    private function fetchLatestCommitMessage(Ploi $ploi, int $serverId, int $siteId): ?string
    {
        try {
            $logResponse = $ploi->servers($serverId)->sites($siteId)->logs();
            $entries = $logResponse->getData() ?? [];

            if (empty($entries)) {
                return null;
            }

            $latestEntry = $entries[0];
            $endpoint = "servers/{$serverId}/sites/{$siteId}/log/{$latestEntry->id}";
            $detail = $ploi->makeAPICall($endpoint);
            $content = $detail->getData()->content ?? '';

            return $this->extractCommitMessage($content);
        } catch (Throwable $exception) {
            Log::debug('fetchLatestCommitMessage failed', ['site_id' => $siteId, 'error' => $exception->getMessage()]);

            return null;
        }
    }

    private function extractCommitMessage(string $content): ?string
    {
        if (preg_match('/HEAD is now at [0-9a-f]{7,40}\s+(.+)/i', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function cleanupStaleRecords(Account $account): void
    {
        $account->servers()
            ->whereNotNull('project_id')
            ->whereDoesntHave('project')
            ->update(['project_id' => null]);
    }
}
