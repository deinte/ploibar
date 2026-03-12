<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\System;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        config(['app.timezone' => System::timezone()]);
        date_default_timezone_set(System::timezone());

        MenuBar::create()
            ->route('menu-bar')
            ->width(400)
            ->height(520)
            ->blendBackgroundBehindWindow()
            ->resizable(false)
            ->withContextMenu(
                Menu::make(
                    Menu::label('PloiBar v0.0.1'),
                    Menu::separator(),
                    Menu::link(config('services.ploi.url'), 'Open Ploi Dashboard'),
                    Menu::separator(),
                    Menu::quit(),
                )
            );
    }

    public function phpIni(): array
    {
        return [];
    }
}
