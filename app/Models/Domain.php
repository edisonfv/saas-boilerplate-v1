<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Stancl\Tenancy\Database\Models\Domain as StanclDomain;

class Domain extends StanclDomain
{
    use UsesUuidPrimaryKey;
}
