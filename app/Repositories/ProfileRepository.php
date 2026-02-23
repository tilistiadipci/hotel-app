<?php

namespace App\Repositories;

use App\Models\UserProfile;

class ProfileRepository extends BaseRepository
{
    public function __construct(UserProfile $userProfile)
    {
        parent::__construct($userProfile);
    }

}
