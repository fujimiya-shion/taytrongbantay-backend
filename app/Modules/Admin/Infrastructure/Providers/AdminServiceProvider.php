<?php

namespace App\Modules\Admin\Infrastructure\Providers;

use App\Modules\Admin\Application\Policies\RolePolicy;
use App\Modules\Admin\Domain\Port\Out\Persistence\AdminRepositoryInterface;
use App\Modules\Admin\Infrastructure\Persistence\Implementations\AdminRepository;
use Gate;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Permission\Models\Role;

class AdminServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Admin';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'admin';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
        FilamentAdminPanelProvider::class,
    ];

    public array $bindings = [
        AdminRepositoryInterface::class => AdminRepository::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function boot(): void {
        Gate::policy(Role::class, RolePolicy::class);
    }
}
