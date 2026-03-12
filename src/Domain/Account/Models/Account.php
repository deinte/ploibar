<?php

namespace Domain\Account\Models;

use Domain\Ploi\Models\Project;
use Domain\Ploi\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ploi\Ploi;

class Account extends Model
{
    protected $fillable = ['label', 'api_token', 'is_active'];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function ploiClient(): Ploi
    {
        return new Ploi($this->api_token);
    }
}
