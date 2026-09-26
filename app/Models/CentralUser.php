<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\CentralUserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * Platform staff (super-admin, support, billing, sales). Independent from the
 * per-tenant `User` model and ACL — see docs/architecture/plans-modules-permissions.md
 * section 5.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class CentralUser extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<CentralUserFactory> */
    use HasFactory, HasRoles, Notifiable, UsesUuidPrimaryKey;

    protected string $guard_name = 'central';

    /**
     * Set when this staff member attends booked support sessions.
     *
     * @return HasOne<SupportTechnician, $this>
     */
    public function supportTechnician(): HasOne
    {
        return $this->hasOne(SupportTechnician::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
