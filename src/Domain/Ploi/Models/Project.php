<?php

namespace Domain\Ploi\Models;

use Domain\Account\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['account_id', 'ploi_id', 'title'];

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

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }
}
