<?php

namespace Domain\Ploi\DataTransferObjects;

final readonly class SiteStatus
{
    public function __construct(
        public int $siteId,
        public string $domain,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function hasChanged(): bool
    {
        return $this->oldStatus !== $this->newStatus;
    }
}
