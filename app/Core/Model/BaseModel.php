<?php

declare(strict_types=1);

namespace App\Core\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Base cho mọi domain model (tương đương BaseEntity).
 * id + created_at/updated_at Eloquent đã tự xử lý (thay cho BaseEntityObserver).
 */
abstract class BaseModel extends Model {}
