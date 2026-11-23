<?php

namespace App\Modules\Admin\Infrastructure\Seeders;

use App\Core\Support\Criteria\QueryCriteria;
use App\Modules\Admin\Domain\Port\Out\Persistence\AdminRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Log;

class AdminDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config("admin.account.email");
        $password = config('admin.account.password');

        $this->command->info("Seeding super admin user: $email");

        try {
            app(AdminRepositoryInterface::class)
                ->createOrUpdate(
                    QueryCriteria::make()->where('email', $email),
                    ['email' => $email, 'password' => $password, 'name' => 'Administrator']
                );
            $this->command->info('Create super admin user successfully');
        } catch (ModelNotFoundException $exception) {
            $this->command->error("Cannot create user admin user: " . $exception->getMessage());
            throw $exception;
        }
    }
}
