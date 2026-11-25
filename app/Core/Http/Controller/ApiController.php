<?php

declare(strict_types=1);

namespace App\Core\Http\Controller;

use App\Core\Trait\ApiBehavior;

abstract class ApiController extends Controller
{
    use ApiBehavior;
}
