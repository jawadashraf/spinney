<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Yeastar;

use App\Filament\Resources\Calls\CallResource;
use App\Http\Resources\Yeastar\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class UserController
{
    /**
     * Users who take liaison calls, so the PBX can map its extensions to them.
     */
    public function index(): AnonymousResourceCollection
    {
        $users = CallResource::scopeAssignableUsers(User::query())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return UserResource::collection($users);
    }
}
