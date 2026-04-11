<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateServiceUser extends CreateRecord
{
    protected static string $resource = ServiceUserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Extract password if provided
        $password = $data['password'] ?? Str::random(12);
        unset($data['password']);

        // 2. Create the ServiceUser (People) record
        /** @var ServiceUser $record */
        $record = parent::handleRecordCreation($data);

        // 3. Create the linked User account
        $user = User::create([
            'name' => $record->name,
            'email' => $record->email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('service_user');

        // 4. Link User to ServiceUser
        $record->update(['user_id' => $user->id]);

        return $record;
    }
}
