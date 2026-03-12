<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        MenuBar::create()
            ->route('menu-bar')
            ->width(400)
            ->height(520)
            ->blendBackgroundBehindWindow()
            ->resizable(false)
            ->withContextMenu(
                Menu::make(
                    Menu::label('PloiBar v1.0'),
                    Menu::separator(),
                    Menu::link('https://ploi.io', 'Open Ploi Dashboard'),
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
