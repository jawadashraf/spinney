<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\HasTeam;
use App\Models\Pivots\DepartmentUser;

final class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;
    use HasTeam;

    // protected $fillable = [
    //     'team_id',
    //     'name',
    //     'description',
    // ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(DepartmentUser::class)
            ->withPivot('team_id')
            ->withTimestamps();
    }
}
