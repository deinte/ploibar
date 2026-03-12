<?php

namespace Domain\Ploi\DataTransferObjects;

final readonly class ServerStatus
{
    public function __construct(
        public int $serverId,
        public string $name,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function hasChanged(): bool
    {
        return $this->oldStatus !== $this->newStatus;
    }
}
