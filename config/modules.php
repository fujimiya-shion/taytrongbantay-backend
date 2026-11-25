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
    'namespace' => 'App\\Module',

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
        // Không sinh composer.json riêng cho module vì App\Module\* đã được
        // autoload bởi PSR-4 "App\\" => "app/" của root composer.json.
        'files' => [
            'routes/api' => 'Inbound/Rest/V1/Route/api.php',
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
             *      'CUSTOM_KEY' => \App\Module\Support\Replacements\CustomKey::class,
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
        'modules' => base_path('app/Module'),

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
            // Module-first, 4 tầng (bản rút gọn của rada360-deploy-tool):
            //
            //   Inbound ──> Application/UseCase ──> Domain/Port/Out (interface)
            //                                              ▲
            //                    Infrastructure/Persistence/Implementation (cài đặt)
            //
            // - Controller inject thẳng class UseCase (không cần Port/In).
            // - UseCase inject <Model>RepositoryInterface (Domain/Port/Out/Persistence),
            //   interface extends Read/WriteRepositoryInterface + method riêng.
            // - Cài đặt extends BasePersistenceRepository, bind trong <Module>ServiceProvider.
            // - Request -> Command: FormRequest::toCommand() (không cần Mapper).
            //
            // Key không thuộc nwidart (app-command, app-output, repository-interface)
            // dùng để sinh folder khi module:make và cho make:feature đọc đường dẫn.
            // =================================================================

            // -----------------------------------------------------------------
            // 1. DOMAIN: Eloquent model, enum, observer.
            // -----------------------------------------------------------------
            'model'                => ['path' => 'Domain/Model', 'generate' => true],
            'enums'                => ['path' => 'Domain/Enum', 'generate' => false],
            'observer'             => ['path' => 'Domain/Observer', 'generate' => false],
            'casts'                => ['path' => 'Domain/Cast', 'generate' => false],
            'scopes'               => ['path' => 'Domain/Scope', 'generate' => false],
            'event'                => ['path' => 'Domain/Event', 'generate' => false],
            'exceptions'           => ['path' => 'Domain/Exception', 'generate' => false],
            'repository-interface' => ['path' => 'Domain/Port/Out/Persistence', 'generate' => true],
            'interfaces'           => ['path' => 'Domain/Port/Out', 'generate' => false],

            // -----------------------------------------------------------------
            // 2. APPLICATION: command (input), output, usecase.
            //    php artisan module:make-service CreateUserUseCase Identity
            // -----------------------------------------------------------------
            'app-command'          => ['path' => 'Application/Command', 'generate' => true],
            'app-output'           => ['path' => 'Application/Output', 'generate' => true],
            'services'             => ['path' => 'Application/UseCase', 'generate' => true],
            'actions'              => ['path' => 'Application/UseCase', 'generate' => false],
            'policies'             => ['path' => 'Application/Policy', 'generate' => false],

            // -----------------------------------------------------------------
            // 3. INBOUND: REST controller, request/resource, console.
            // -----------------------------------------------------------------
            'controller'           => ['path' => 'Inbound/Rest/V1/Controller', 'generate' => true],
            'request'              => ['path' => 'Inbound/Rest/V1/Dto', 'generate' => true],
            'resource'             => ['path' => 'Inbound/Rest/V1/Dto', 'generate' => false],
            'filter'               => ['path' => 'Inbound/Rest/V1/Middleware', 'generate' => false],
            'rules'                => ['path' => 'Inbound/Rest/V1/Rule', 'generate' => false],
            'routes'               => ['path' => 'Inbound/Rest/V1/Route', 'generate' => true],
            'command'              => ['path' => 'Inbound/Console', 'generate' => false],
            'command_replacements' => ['path' => 'Inbound/Console/Replacement', 'generate' => false],

            // -----------------------------------------------------------------
            // 4. INFRASTRUCTURE: repository, migration, seeder, provider.
            //    Repository sinh bằng make:feature (kèm interface + binding).
            // -----------------------------------------------------------------
            'repository'           => ['path' => 'Infrastructure/Persistence/Implementation', 'generate' => true],
            'migration'            => ['path' => 'Infrastructure/Persistence/Migration', 'generate' => true],
            'factory'              => ['path' => 'Infrastructure/Persistence/Factory', 'generate' => false],
            'seeder'               => ['path' => 'Infrastructure/Seeder', 'generate' => true],

            'listener'             => ['path' => 'Infrastructure/Messaging/Listener', 'generate' => false],
            'jobs'                 => ['path' => 'Infrastructure/Messaging/Job', 'generate' => false],
            'job-middleware'       => ['path' => 'Infrastructure/Messaging/Job/Middleware', 'generate' => false],
            'channels'             => ['path' => 'Infrastructure/Messaging/Broadcasting', 'generate' => false],
            'notifications'        => ['path' => 'Infrastructure/Notification', 'generate' => false],
            'emails'               => ['path' => 'Infrastructure/Mail', 'generate' => false],

            'provider'             => ['path' => 'Infrastructure/Provider', 'generate' => true],
            'route-provider'       => ['path' => 'Infrastructure/Provider', 'generate' => true],
            'event-provider'       => ['path' => 'Infrastructure/Provider', 'generate' => false],
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
            'component-class'      => ['path' => 'Inbound/Web/Component', 'generate' => false],
            'component-view'       => ['path' => 'Inbound/Web/view/component', 'generate' => false],
            'views'                => ['path' => 'Inbound/Web/view', 'generate' => false],
            'assets'               => ['path' => 'Inbound/Web/asset', 'generate' => false],
            'inertia'              => ['path' => 'Inbound/Web/js/Page', 'generate' => false],
            'inertia-components'   => ['path' => 'Inbound/Web/js/Component', 'generate' => false],

            // -----------------------------------------------------------------
            // 7. TESTS
            // -----------------------------------------------------------------
            'test-feature'         => ['path' => 'Test/Feature', 'generate' => true],
            'test-unit'            => ['path' => 'Test/Unit', 'generate' => true],
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
