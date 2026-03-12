<?php

namespace Domain\Ploi\Models;

use Domain\Account\Models\Account;
use Domain\Ploi\Concerns\HasStatusColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasStatusColor;

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
}
