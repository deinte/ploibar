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
        'monitoring_data',
    ];

    protected function casts(): array
    {
        return [
            'ploi_id' => 'integer',
            'monitoring_data' => 'array',
        ];
    }

    public function cpuUsage(): ?float
    {
        return data_get($this->monitoring_data, 'cpu_usage')
            ?? data_get($this->monitoring_data, 'cpu')
            ?? data_get($this->monitoring_data, 'load.cpu');
    }

    public function memoryUsage(): ?float
    {
        return data_get($this->monitoring_data, 'ram')
            ?? data_get($this->monitoring_data, 'memory_usage')
            ?? data_get($this->monitoring_data, 'memory');
    }

    public function diskUsage(): ?float
    {
        return data_get($this->monitoring_data, 'disk_usage')
            ?? data_get($this->monitoring_data, 'disk')
            ?? data_get($this->monitoring_data, 'load.disk');
    }

    public function hasMonitoring(): bool
    {
        return $this->cpuUsage() !== null
            || $this->memoryUsage() !== null
            || $this->diskUsage() !== null;
    }

    public function monitoringStatus(): string
    {
        $metrics = array_filter([
            $this->cpuUsage(),
            $this->memoryUsage(),
            $this->diskUsage(),
        ], fn ($v) => $v !== null);

        if (empty($metrics)) {
            return 'healthy';
        }

        $max = max($metrics);

        if ($max > 90) {
            return 'critical';
        }

        if ($max > 80) {
            return 'warning';
        }

        return 'healthy';
    }

    public function highestUsageMetric(): ?array
    {
        $metrics = array_filter([
            'CPU' => $this->cpuUsage(),
            'MEM' => $this->memoryUsage(),
            'DISK' => $this->diskUsage(),
        ], fn ($v) => $v !== null);

        if (empty($metrics)) {
            return null;
        }

        $label = array_keys($metrics, max($metrics))[0];

        return ['label' => $label, 'value' => $metrics[$label]];
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
