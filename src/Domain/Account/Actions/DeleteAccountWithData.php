<?php

namespace Domain\Account\Actions;

use Domain\Account\Models\Account;
use Illuminate\Support\Facades\DB;

class DeleteAccountWithData
{
    public function execute(Account $account): void
    {
        DB::transaction(fn () => $account->delete());
    }
}
