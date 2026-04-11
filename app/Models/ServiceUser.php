<?php

declare(strict_types=1);

namespace App\Models;

use Parental\HasParent;

final class ServiceUser extends People
{
    use HasParent;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<ServiceUserProfile, $this>
     */
    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ServiceUserProfile::class, 'person_id');
    }
}
