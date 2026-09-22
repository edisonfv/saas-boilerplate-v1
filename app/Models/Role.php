<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use UsesUuidPrimaryKey;
}
