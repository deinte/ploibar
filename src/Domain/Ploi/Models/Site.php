<?php

namespace Domain\Ploi\Models;

use Domain\Ploi\Concerns\HasStatusColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasStatusColor;

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
}
