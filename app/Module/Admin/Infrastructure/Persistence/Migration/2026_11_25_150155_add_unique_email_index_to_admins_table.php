<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email là khoá đăng nhập của admin: phải duy nhất và được index để tra cứu nhanh.
 *
 * Bảng dùng SoftDeletes, nên chỉ ràng buộc duy nhất trên các bản ghi chưa xoá
 * (partial index). Nhờ vậy xoá mềm một admin xong vẫn tạo lại được admin cùng email.
 * PostgreSQL và SQLite hỗ trợ partial index; driver khác dùng unique index thường.
 */
return new class extends Migration
{
    private const INDEX = 'admins_email_unique';

    public function up(): void
    {
        $partial = in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true);

        DB::statement(
            'CREATE UNIQUE INDEX '.self::INDEX.' ON admins (email)'
            .($partial ? ' WHERE deleted_at IS NULL' : '')
        );
    }

    public function down(): void
    {
        DB::statement(match (DB::getDriverName()) {
            'mysql', 'mariadb' => 'DROP INDEX '.self::INDEX.' ON admins',
            default => 'DROP INDEX IF EXISTS '.self::INDEX,
        });
    }
};
