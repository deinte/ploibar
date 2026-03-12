<?php

namespace Domain\Sync\Actions;

use Domain\Account\Models\Account;
use Native\Desktop\Facades\Notification;

class DetectStatusChanges
{
    public function execute(Account $account, array $oldServerStatuses, array $oldSiteStatuses): void
    {
        $account->load('servers.sites');

        foreach ($account->servers as $server) {
            $oldStatus = $oldServerStatuses[$server->id] ?? null;

            if ($oldStatus && $oldStatus !== $server->status) {
                Notification::title("Server: {$server->name}")
                    ->message("Status changed: {$oldStatus} → {$server->status}")
                    ->show();
            }

            foreach ($server->sites as $site) {
                $oldSiteStatus = $oldSiteStatuses[$site->id] ?? null;

                if ($oldSiteStatus && $oldSiteStatus !== $site->status) {
                    Notification::title("Site: {$site->domain}")
                        ->message("Status changed: {$oldSiteStatus} → {$site->status}")
                        ->show();
                }
            }
        }
    }
}
