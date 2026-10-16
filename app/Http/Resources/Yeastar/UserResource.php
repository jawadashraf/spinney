<?php

declare(strict_types=1);

namespace App\Http\Resources\Yeastar;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A liaison the PBX can associate with an extension (UserAssociation scenario).
 *
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array{id: string, first_name: string|null, last_name: string|null, email: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
        ];
    }
}
