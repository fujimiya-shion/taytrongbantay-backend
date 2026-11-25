<?php

declare(strict_types=1);

namespace App\Module\Admin\Infrastructure\Seeder;

use App\Module\Admin\Domain\Exception\EmailOrPasswordEmptyException;
use App\Module\Admin\Domain\Model\Admin;
use App\Module\Admin\Domain\Port\Out\Persistence\AdminRepositoryInterface;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminDatabaseSeeder extends Seeder
{
    /** Guard của panel Filament (FilamentAdminPanelProvider::authGuard). */
    private const GUARD = 'admin';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('admin.account.email');
        $password = config('admin.account.password');

        if (! $email || ! $password) {
            throw new EmailOrPasswordEmptyException;
        }

        $this->command->info("Seeding super admin user: $email");

        $admin = app(AdminRepositoryInterface::class)->upsertByEmail($email, [
            'password' => $password,
            'name' => 'Administrator',
        ]);
        $this->command->info('Create super admin user successfully');

        $this->assignSuperAdminRole($admin);
    }

    /**
     * Làm thay `shield:generate` + `shield:super-admin`, để chỉ cần `db:seed` là đăng nhập được:
     *  1. sinh permission cho mọi Resource/Page/Widget của panel (chỉ permission, không sinh file Policy);
     *  2. tạo role super_admin (guard admin) nếu chưa có, gán TẤT CẢ permission của guard admin;
     *  3. gán role cho tài khoản.
     *
     * config/filament-shield.php đặt define_via_gate = false, nên super_admin chỉ có quyền
     * qua permission được gán thật (không có Gate::before bỏ qua mọi kiểm tra).
     * Không có role thì Admin::canAccessPanel() trả false, Filament báo sai mật khẩu.
     * Chạy lại seeder nhiều lần vẫn an toàn.
     */
    private function assignSuperAdminRole(Admin $admin): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--option' => 'permissions',
            '--panel' => self::GUARD,
        ]);

        $role = Role::findOrCreate(Utils::getSuperAdminName(), self::GUARD);
        $role->syncPermissions(Permission::query()->where('guard_name', self::GUARD)->get());

        $admin->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info("Assigned role [{$role->name}] with {$role->permissions()->count()} permissions to admin #{$admin->id}");
    }
}
