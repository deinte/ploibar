<?php

namespace Domain\Ploi\Concerns;

trait HasStatusColor
{
    public function statusColor(): string
    {
        return match ($this->status) {
            'running', 'active', 'completed' => '#34C759',
            'deploying', 'installing', 'pending' => '#FF9F0A',
            'stopped', 'error', 'failed' => '#FF3B30',
            default => '#8E8E93',
        };
    }
}
