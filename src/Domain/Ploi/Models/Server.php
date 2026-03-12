<?php

namespace Domain\Ploi\Models;

use Domain\Account\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'account_id', 'project_id', 'ploi_id', 'name',
        'ip_address', 'status', 'type', 'php_version',
    ];

    protected function casts(): array
    {
        return [
            'ploi_id' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'running', 'active' => '#34C759',
            'deploying', 'installing' => '#FF9F0A',
            'stopped', 'error' => '#FF3B30',
            default => '#8E8E93',
        };
    }
}
