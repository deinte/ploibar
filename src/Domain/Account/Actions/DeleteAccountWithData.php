<?php

namespace Domain\Account\Actions;

use Domain\Account\Models\Account;

class DeleteAccountWithData
{
    public function execute(Account $account): void
    {
        $account->delete();
    }
}
