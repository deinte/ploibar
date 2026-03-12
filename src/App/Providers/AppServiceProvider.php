<?php

namespace App\Providers;

use App\Listeners\SyncOnMenuBarOpen;
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
        Livewire::component('status-dashboard', \App\Livewire\StatusDashboard::class);
        Livewire::component('account-manager', \App\Livewire\AccountManager::class);

        Event::listen(MenuBarShown::class, SyncOnMenuBarOpen::class);
    }
}
