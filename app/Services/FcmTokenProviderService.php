<?php

namespace App\Services;

use App\Models\User;

class FcmTokenProviderService
{

    public function getTokensByRole(int $role_id):array
    {
        return  User::where('role_id',$role_id)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->toArray();

    }
}
