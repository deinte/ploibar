<?php

namespace Domain\Account\Actions;

use Ploi\Ploi;

class TestAccountConnection
{
    public function execute(string $apiToken): bool
    {
        try {
            $ploi = new Ploi($apiToken);
            $ploi->user()->get();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
