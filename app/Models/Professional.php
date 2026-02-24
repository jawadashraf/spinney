<?php

declare(strict_types=1);

namespace App\Models;

use Parental\HasParent;

final class Professional extends People
{
    use HasParent;
}
