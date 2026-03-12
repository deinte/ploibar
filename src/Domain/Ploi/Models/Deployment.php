<?php

namespace Domain\Ploi\Models;

use Domain\Ploi\Concerns\HasStatusColor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    use HasStatusColor;

    protected $fillable = [
        'site_id', 'status', 'triggered_at', 'completed_at', 'source', 'commit_message',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeRecent(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('triggered_at')->limit($limit);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->whereHas('site.server', fn (Builder $q) => $q->where('account_id', $accountId));
    }

    public function triggeredAgo(): string
    {
        return $this->triggered_at->diffForHumans();
    }
}
