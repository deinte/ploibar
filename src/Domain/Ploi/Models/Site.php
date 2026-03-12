<?php

namespace Domain\Ploi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'server_id', 'ploi_id', 'domain', 'status',
        'project_type', 'last_deploy_at',
    ];

    protected function casts(): array
    {
        return [
            'ploi_id' => 'integer',
            'last_deploy_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active', 'running' => '#34C759',
            'deploying', 'installing' => '#FF9F0A',
            'stopped', 'error' => '#FF3B30',
            default => '#8E8E93',
        };
    }
}
