<p align="center">
  <img src="public/icon.png" alt="PloiBar" width="128">
</p>

<h1 align="center">PloiBar</h1>

<p align="center">
  A macOS menu bar app for monitoring and managing your <a href="https://ploi.io">Ploi.io</a> servers.
</p>

<p align="center">
  <a href="https://github.com/deinte/ploibar/actions/workflows/ci.yml"><img src="https://github.com/deinte/ploibar/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License: MIT"></a>
</p>

## Features

- **Multi-account support** — manage multiple Ploi accounts with tabbed navigation
- **Project grouping** — servers organized by Ploi projects, with an "Unassigned" group for standalone servers
- **One-click deploys** — trigger deployments directly from the menu bar
- **Deployment history** — track recent deploys per site with status and timestamps
- **Activity feed** — global view of all recent deployments across accounts
- **Native macOS design** — vibrancy, system fonts, dark/light mode, system colors
- **Auto-sync** — refreshes data on menu bar open and every 60 seconds
- **Status notifications** — native macOS notifications when server/site status changes
- **Copyable server details** — IP address and SSH command with one-click copy

## Screenshots

```
┌──────────────────────────────────────┐
│ Personal │ Agency │ Client Co  │  ⚙  │
├──────────────────────────────────────┤
│                                      │
│ MY SAAS APP                          │
│ ▼ ● production        3 sites       │
│   ┌──────────────────────────────┐   │
│   │ IP  192.168.1.1  │ SSH ploi@│   │
│   │ ● app.example.com    active │   │
│   │ ● api.example.com    active │   │
│   └──────────────────────────────┘   │
│ ▶ ● staging            2 sites      │
│                                      │
│ UNASSIGNED                           │
│ ▶ ● dev-box            1 site       │
│                                      │
├──────────────────────────────────────┤
│   Last synced: 2:35 PM      ⏱  ↻   │
└──────────────────────────────────────┘
```

## Requirements

- macOS 12+
- PHP 8.2+
- Node.js 18+

## Installation

### From source

```bash
git clone https://github.com/deinte/ploibar.git
cd ploibar
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Development

```bash
composer native:dev
```

This starts the NativePHP Electron app with Vite hot-reload.

### Build

```bash
php artisan native:build
```

Produces a `.app` bundle in the `build/` directory.

## Tech Stack

- [Laravel 12](https://laravel.com) — application framework
- [Livewire](https://livewire.laravel.com) — reactive UI components
- [NativePHP](https://nativephp.com) — Electron wrapper for PHP apps
- [Ploi PHP SDK](https://github.com/ploi/ploi-php-sdk) — Ploi API client

## Project Structure

```
src/
├── Domain/
│   ├── Account/          # Account management (models, actions)
│   ├── Ploi/             # Ploi data models (Server, Site, Project, Deployment)
│   └── Sync/             # API sync jobs and status change detection
├── App/
│   ├── Livewire/         # StatusDashboard, AccountManager components
│   ├── Listeners/        # Menu bar event listeners
│   └── Providers/        # NativePHP service provider
```

## License

MIT
