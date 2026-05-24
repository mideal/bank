<?php

namespace App\Repositories;

use App\Models\Account;

class AccountRepository
{
    public function findForUpdate(int $id): ?Account
    {
        return Account::lockForUpdate()->find($id);
    }

    public function save(Account $account): Account
    {
        $account->save();

        return $account;
    }
}
