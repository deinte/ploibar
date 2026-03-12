<?php

namespace App\Providers;

use App\Listeners\SyncOnMenuBarOpen;
use App\Livewire\AccountManager;
use App\Livewire\StatusDashboard;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Native\Desktop\Events\MenuBar\MenuBarShown;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::component('status-dashboard', StatusDashboard::class);
        Livewire::component('account-manager', AccountManager::class);

        Event::listen(MenuBarShown::class, SyncOnMenuBarOpen::class);
    }
}
