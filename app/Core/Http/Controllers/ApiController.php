<?php
declare(strict_types=1);

namespace App\Core\Http\Controllers;

use App\Core\Traits\ApiBehavior;

abstract class ApiController extends Controller {
    use ApiBehavior;
}