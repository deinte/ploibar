<?php

namespace Domain\Sync\Jobs;

use Domain\Account\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncAllAccounts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Account::where('is_active', true)
            ->pluck('id')
            ->each(fn (int $id) => SyncAccountData::dispatch($id));
    }
}
