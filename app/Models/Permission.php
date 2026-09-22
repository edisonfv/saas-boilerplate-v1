<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use UsesUuidPrimaryKey;
}
