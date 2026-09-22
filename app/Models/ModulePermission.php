<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\ModulePermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Catalog/blueprint of permissions a module offers. Not Spatie's authorization
 * table — the real permission rows live in each tenant's own database (and the
 * central ACL uses Spatie's own, separate "permissions" table — see
 * docs/architecture/plans-modules-permissions.md section 5).
 *
 * @property string $id
 * @property string $module_id
 * @property string $slug
 * @property string|null $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['module_id', 'slug', 'label'])]
class ModulePermission extends Model
{
    /** @use HasFactory<ModulePermissionFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
