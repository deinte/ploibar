# CLAUDE.md

## Project Overview

PloiBar is a macOS menu bar app for monitoring and managing Ploi.io servers. Built with Laravel 12, Livewire 4, NativePHP 2.1, and the Ploi PHP SDK 2.0.

## Commands

```bash
# First-time setup (install deps, generate key, migrate, build assets)
composer setup

# Run as native desktop app with Vite HMR
composer native:dev

# Run as web app (server + queue + logs + vite)
composer dev

# Tests
composer test
php artisan test --filter=TestName

# Code style
vendor/bin/pint            # fix
vendor/bin/pint --test     # check only
```

## Architecture

### Custom PSR-4 Autoloading

Source lives in `src/` instead of the default `app/` directory:

- `App\` → `src/App/` — application layer (providers, listeners, Livewire components, HTTP)
- `Domain\` → `src/Domain/` — business logic organized by domain

### Domain Structure

- `Domain\Account` — Account model (holds encrypted API token), actions (TestAccountConnection, DeleteAccountWithData)
- `Domain\Ploi` — Server, Site, Project, Deployment models; HasStatusColor concern
- `Domain\Sync` — SyncAllAccounts and SyncAccountData jobs; DetectStatusChanges action

### Key Classes

- `App\Providers\NativeAppServiceProvider` — configures the menu bar (dimensions, vibrancy, context menu)
- `App\Providers\AppServiceProvider` — registers Livewire components and event listeners
- `App\Listeners\SyncOnMenuBarOpen` — dispatches SyncAllAccounts when menu bar opens
- `App\Livewire\StatusDashboard` — main dashboard view
- `App\Livewire\AccountManager` — account CRUD

### Event-Driven Sync Flow

```
MenuBarShown → SyncOnMenuBarOpen → SyncAllAccounts → SyncAccountData (per account)
```

SyncAccountData syncs servers, sites, projects, and deployments from the Ploi API, then runs DetectStatusChanges for notifications.

## NativePHP Reference

### Facades

```php
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\System;
use Native\Desktop\Facades\Notification;
```

### NativeAppServiceProvider

Implements `ProvidesPhpIni`. The `boot()` method configures the menu bar:

```php
MenuBar::create()
    ->route('menu-bar')
    ->width(400)
    ->height(520)
    ->blendBackgroundBehindWindow()
    ->resizable(false)
    ->withContextMenu(Menu::make(...));
```

### Config

`config/nativephp.php` — app ID (`com.ploibar.app`), version, updater (GitHub releases), queue workers.

### Artisan Commands

- `php artisan native:serve` — run as native app (used by `composer native:dev`)
- `php artisan native:build` — build distributable
- `php artisan native:install` — install NativePHP scaffolding
- `php artisan native:reset` — reset NativePHP state

### Key Events

- `Native\Desktop\Events\MenuBar\MenuBarShown` — fired when menu bar is opened

### Queue Workers

NativePHP auto-starts queue workers defined in `config/nativephp.php` under `queue_workers`. The default worker processes the `default` queue.

## Ploi PHP SDK Reference

### Entry Point

```php
use Ploi\Ploi;

$ploi = new Ploi($apiToken);
// In this project: $account->ploiClient()
```

### Fluent Resource Chaining

```php
$ploi->servers()->get();                          // all servers
$ploi->servers($id)->sites()->get();              // sites for a server
$ploi->servers($id)->sites($id)->logs();          // site logs
$ploi->projects()->get();                         // all projects
```

### Resource Hierarchy

```
Ploi → Server → Site → [Certificate, Repository, Queue, Deployment, App, Environment, Alias, ...]
Ploi → Server → [Database, Cronjob, Daemon, SshKey, Service, NetworkRule, SystemUser, Opcache, ...]
Ploi → [Project, Script, StatusPage, User, WebserverTemplate, FileBackup]
```

### Response Handling

```php
$response = $ploi->servers()->get();
$response->getData();   // parsed data (array of objects)
$response->getJson();   // raw JSON
$response->toArray();   // array format
```

### Direct API Calls

```php
$ploi->makeAPICall($endpoint);  // for endpoints not covered by resource classes
```

### Pagination

Resources with `HasPagination` trait support `->page($number, $perPage)`.

## Conventions

- Models store `ploi_id` (remote API ID) alongside the local `id`
- API tokens are stored encrypted on the Account model
- Sync jobs use `updateOrCreate` with `ploi_id` as the match key, then delete stale records
- Status colors are provided by the `HasStatusColor` concern on Server and Site models
