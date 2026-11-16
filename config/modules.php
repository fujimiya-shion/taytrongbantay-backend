<?php

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Providers\ConsoleServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Default module namespace.
    |
    */
    'namespace' => 'App\\Modules',

    /*
    |--------------------------------------------------------------------------
    | Vapor Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Indicates if the application is running on Laravel Vapor.
    | When enabled, cached services path will be set to a writable location.
    |
    */
    'vapor_maintenance_mode' => env('VAPOR_MAINTENANCE_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Module Stubs
    |--------------------------------------------------------------------------
    |
    | Default module stubs.
    |
    */
    'stubs' => [
        // Stub trong stubs/nwidart-stubs override stub của vendor;
        // stub nào không có thì nwidart tự fallback về vendor.
        'enabled' => true,
        'path' => base_path('stubs/nwidart-stubs'),

        // API-only: không sinh web route, views, assets, vite, package.json.
        // Không sinh composer.json riêng cho module vì App\Modules\* đã được
        // autoload bởi PSR-4 "App\\" => "app/" của root composer.json.
        'files' => [
            'routes/api' => 'Inbound/Rest/V1/Routes/api.php',
            'scaffold/config' => 'Config/config.php',
        ],
        'replacements' => [
            /**
             * Define custom replacements for each section.
             * You can now specify a class name that extends
             * \Nwidart\Modules\Support\ReplacementKeyCommand for dynamic values.
             *
             * Example:
             *
             * 'composer' => [
             *      // Map the UPPERCASE token to your command class
             *      'CUSTOM_KEY' => \App\Modules\Support\Replacements\CustomKey::class,
             *      // You can still list built-in tokens by their names
             *      'LOWER_NAME',
             *      'STUDLY_NAME',
             *      // ...
             * ],
             *
             * The command class must extend ReplacementKeyCommand and implement handle(): string
             * to return the replacement text. The text is inserted as returned: in JSON stubs
             * (json, composer, ...) escape it yourself, for example `\` as `\\` and `"` as `\"`.
             *
             * Note: Keys should be in UPPERCASE.
             */
            'routes/web' => ['LOWER_NAME', 'STUDLY_NAME', 'PLURAL_LOWER_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'routes/api' => ['LOWER_NAME', 'STUDLY_NAME', 'PLURAL_LOWER_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'vite' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME'],
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'views/index' => ['LOWER_NAME'],
            'views/master' => ['LOWER_NAME', 'STUDLY_NAME', 'KEBAB_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
            'composer' => [
                'LOWER_NAME',
                'STUDLY_NAME',
                'VENDOR',
                'AUTHOR_NAME',
                'AUTHOR_EMAIL',
                'MODULE_NAMESPACE',
                'PROVIDER_NAMESPACE',
                'APP_FOLDER_NAME',
            ],
        ],
        'gitkeep' => true,
    ],
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path is used to save the generated module.
        | This path will also be added automatically to the list of scanned folders.
        |
        */
        'modules' => base_path('app/Modules'),

        /*
        |--------------------------------------------------------------------------
        | Modules assets path
        |--------------------------------------------------------------------------
        |
        | Here you may update the modules' assets path.
        |
        */
        'assets' => public_path('modules'),

        /*
        |--------------------------------------------------------------------------
        | The migrations' path
        |--------------------------------------------------------------------------
        |
        | Where you run the 'module:publish-migration' command, where do you publish the
        | the migration files?
        |
        */
        'migration' => base_path('database/migrations'),

        /*
        |--------------------------------------------------------------------------
        | The app path
        |--------------------------------------------------------------------------
        |
        | The folder inside each module that holds its classes. The module
        | composer.json maps the module namespace to this folder.
        | For example, you can change it to 'src/' or 'domain/'.
        |
        | When you change it, move the class generator paths below (the
        | 'app/...' entries) under the same folder. Otherwise module:make
        | stops with an error.
        |
        | Set it to '' to map the module namespace to the module root. Then
        | drop the 'app/' prefix from those paths (for example 'Providers').
        */
        'app_folder' => '',

        /*
        |--------------------------------------------------------------------------
        | Generator path
        |--------------------------------------------------------------------------
        |
        | Customise the paths where the folders will be generated.
        | Setting the generate key to false will not generate that folder.
        | It also skips the stubs.files that the key owns: routes (routes/web,
        | routes/api), views (index, master layout), config (config/config.php)
        | and assets (the asset files, vite.config.js and package.json).
        */
        'generator' => [
            // =================================================================
            // Module-first + Ports & Adapters (mirror rada360-deploy-tool):
            //
            //   Inbound ──> Application/Port/In ──> Application/UseCase ──> Domain
            //                                             │
            //                                             └──> Application/Port/Out
            //                                                        ^
            //                                   Infrastructure ──────┘ (implements)
            //
            // - Application chỉ phụ thuộc Domain + Port/Out, không gọi Eloquent
            //   query builder / Queue / HTTP client trực tiếp.
            // - Inbound (controller, console) chỉ gọi interface ở Port/In.
            // - Infrastructure implement Port/Out (repository adapter, publisher...).
            //
            // Các key không thuộc nwidart (app-command, port-in, ...) chỉ để
            // sinh folder khi module:make. Class bên trong viết tay hoặc dùng
            // module:make-interface / module:make-class với sub-path.
            // =================================================================

            // -----------------------------------------------------------------
            // 1. DOMAIN: entity (Eloquent model), enum, observer, domain DTO.
            //    Giữ phẳng như `modules/<x>/domain` bên Java.
            // -----------------------------------------------------------------
            'model'                => ['path' => 'Domain', 'generate' => true],
            'enums'                => ['path' => 'Domain', 'generate' => false],
            'observer'             => ['path' => 'Domain', 'generate' => false],
            'casts'                => ['path' => 'Domain/Casts', 'generate' => false],
            'scopes'               => ['path' => 'Domain/Scopes', 'generate' => false],
            'event'                => ['path' => 'Domain/Events', 'generate' => false],
            'exceptions'           => ['path' => 'Domain/Exceptions', 'generate' => false],

            // -----------------------------------------------------------------
            // 2. APPLICATION: command (input), output, port/in, port/out, usecase.
            //    php artisan module:make-interface In/ICreateUserUseCase Identity
            //    php artisan module:make-interface Out/IUserRepository Identity
            //    php artisan module:make-service CreateUserUseCase Identity
            // -----------------------------------------------------------------
            'app-command'          => ['path' => 'Application/Command', 'generate' => true],
            'app-output'           => ['path' => 'Application/Output', 'generate' => true],
            'interfaces'           => ['path' => 'Application/Port', 'generate' => false],
            'port-in'              => ['path' => 'Application/Port/In', 'generate' => true],
            'port-out'             => ['path' => 'Application/Port/Out', 'generate' => true],
            'services'             => ['path' => 'Application/UseCase', 'generate' => true],
            'actions'              => ['path' => 'Application/UseCase', 'generate' => false],
            'app-mapper'           => ['path' => 'Application/Mapper', 'generate' => false],
            'policies'             => ['path' => 'Application/Policies', 'generate' => false],

            // -----------------------------------------------------------------
            // 3. INBOUND: REST controller, request/response DTO, mapper, console.
            // -----------------------------------------------------------------
            'controller'           => ['path' => 'Inbound/Rest/V1/Controllers', 'generate' => true],
            'request'              => ['path' => 'Inbound/Rest/V1/Dtos', 'generate' => true],
            'resource'             => ['path' => 'Inbound/Rest/V1/Dtos', 'generate' => false],
            'filter'               => ['path' => 'Inbound/Rest/V1/Middleware', 'generate' => false],
            'rules'                => ['path' => 'Inbound/Rest/V1/Rules', 'generate' => false],
            'routes'               => ['path' => 'Inbound/Rest/V1/Routes', 'generate' => true],
            'inbound-mapper'       => ['path' => 'Inbound/Mapper', 'generate' => true],
            'command'              => ['path' => 'Inbound/Console', 'generate' => false],
            'command_replacements' => ['path' => 'Inbound/Console/Replacements', 'generate' => false],

            // -----------------------------------------------------------------
            // 4. INFRASTRUCTURE: persistence adapter, messaging, seeder, provider.
            //    Eloquent<X>RepositoryAdapter implements Port/Out/I<X>Repository.
            // -----------------------------------------------------------------
            'repository'           => ['path' => 'Infrastructure/Persistence', 'generate' => true],
            'migration'            => ['path' => 'Infrastructure/Persistence/Migrations', 'generate' => true],
            'factory'              => ['path' => 'Infrastructure/Persistence/Factories', 'generate' => false],
            'seeder'               => ['path' => 'Infrastructure/Seeders', 'generate' => true],

            'listener'             => ['path' => 'Infrastructure/Messaging/Listeners', 'generate' => false],
            'jobs'                 => ['path' => 'Infrastructure/Messaging/Jobs', 'generate' => false],
            'job-middleware'       => ['path' => 'Infrastructure/Messaging/Jobs/Middleware', 'generate' => false],
            'channels'             => ['path' => 'Infrastructure/Messaging/Broadcasting', 'generate' => false],
            'notifications'        => ['path' => 'Infrastructure/Notifications', 'generate' => false],
            'emails'               => ['path' => 'Infrastructure/Mail', 'generate' => false],

            'provider'             => ['path' => 'Infrastructure/Providers', 'generate' => true],
            'route-provider'       => ['path' => 'Infrastructure/Providers', 'generate' => true],
            'event-provider'       => ['path' => 'Infrastructure/Providers', 'generate' => false],
            'class'                => ['path' => 'Infrastructure/Support', 'generate' => false],
            'helpers'              => ['path' => 'Infrastructure/Support', 'generate' => false],
            'traits'               => ['path' => 'Infrastructure/Support', 'generate' => false],
            'lang'                 => ['path' => 'Infrastructure/Lang', 'generate' => false],

            // -----------------------------------------------------------------
            // 5. CONFIG (giống `modules/<x>/config` bên Java).
            // -----------------------------------------------------------------
            'config'               => ['path' => 'Config', 'generate' => true],

            // -----------------------------------------------------------------
            // 6. WEB UI: tắt (API-only).
            // -----------------------------------------------------------------
            'component-class'      => ['path' => 'Inbound/Web/Components', 'generate' => false],
            'component-view'       => ['path' => 'Inbound/Web/views/components', 'generate' => false],
            'views'                => ['path' => 'Inbound/Web/views', 'generate' => false],
            'assets'               => ['path' => 'Inbound/Web/assets', 'generate' => false],
            'inertia'              => ['path' => 'Inbound/Web/js/Pages', 'generate' => false],
            'inertia-components'   => ['path' => 'Inbound/Web/js/Components', 'generate' => false],

            // -----------------------------------------------------------------
            // 7. TESTS
            // -----------------------------------------------------------------
            'test-feature'         => ['path' => 'Tests/Feature', 'generate' => true],
            'test-unit'            => ['path' => 'Tests/Unit', 'generate' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Discover of Modules
    |--------------------------------------------------------------------------
    |
    | Here you configure auto discover of module
    | This is useful for simplify module providers.
    |
    */
    'auto-discover' => [
        /*
        |--------------------------------------------------------------------------
        | Migrations
        |--------------------------------------------------------------------------
        |
        | This option for register migration automatically.
        |
        */
        'migrations' => true,

        /*
        |--------------------------------------------------------------------------
        | Translations
        |--------------------------------------------------------------------------
        |
        | This option for register lang file automatically.
        |
        */
        'translations' => false,

    ],

    /*
    |--------------------------------------------------------------------------
    | Package commands
    |--------------------------------------------------------------------------
    |
    | Here you can define which commands will be visible and used in your
    | application. You can add your own commands to merge section.
    |
    */
    'commands' => ConsoleServiceProvider::defaultCommands()
        ->merge([
            // New commands go here
        ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default will scan vendor
    | directory. This is useful if you host the package in packagist website.
    |
    */
    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for the composer.json file, generated by this package
    |
    */
    'composer' => [
        'vendor' => env('MODULE_VENDOR', 'nwidart'),
        'author' => [
            'name' => env('MODULE_AUTHOR_NAME', 'Nicolas Widart'),
            'email' => env('MODULE_AUTHOR_EMAIL', 'n.widart@gmail.com'),
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Choose what laravel-modules will register as custom namespaces.
    | Setting one to false will require you to register that part
    | in your own Service Provider class.
    |
    | Deprecated: these keys are read only by Module::boot() and Module::register(),
    | which the package no longer calls. Module translations are loaded by each
    | module's service provider (and 'auto-discover.translations'); module files
    | are loaded by the package on register. Will be removed in v14.
    |--------------------------------------------------------------------------
    */
    'register' => [
        'translations' => true,
        /**
         * load files on boot or register method
         */
        'files' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activators
    |--------------------------------------------------------------------------
    |
    | You can define new types of activators here, file, database, etc. The only
    | required parameter is 'class'.
    | The file activator will store the activation status in storage/installed_modules
    */
    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
        ],
    ],

    'activator' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Inertia
    |--------------------------------------------------------------------------
    |
    | Default Inertia frontend framework used by make commands when no
    | framework flag (--vue, --react, --svelte) is provided.
    |
    | Supported: "vue", "react", "svelte"
    |
    */
    'inertia' => [
        'frontend' => 'vue',
    ],
];
