<?php

declare(strict_types=1);

namespace App\Modules\Admin\Domain\Model;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable('email', 'password', 'name')]
#[Hidden('password')]
class Admin extends Authenticatable implements FilamentUser
{
    use HasRoles, SoftDeletes;

    protected string $guardName = 'admin';

    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Ai được vào panel:
     * - chỉ panel "admin" (FilamentAdminPanelProvider);
     * - có role super_admin, hoặc có ít nhất một role bất kỳ của guard admin
     *   (panel_user hay role tạo trong màn hình Roles).
     * Tài khoản chưa được gán role nào thì đăng nhập đúng mật khẩu vẫn bị chặn.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        if ($this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        return $this->roles->isNotEmpty();
    }
}
