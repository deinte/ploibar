<?php

namespace App\Listeners;

use Domain\Sync\Jobs\SyncAllAccounts;
use Native\Desktop\Events\MenuBar\MenuBarShown;

class SyncOnMenuBarOpen
{
    public function handle(MenuBarShown $event): void
    {
        SyncAllAccounts::dispatch();
    }
}
