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
        'enabled' => false,
        'path' => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
        'files' => [
            'routes/web' => 'routes/web.php',
            'routes/api' => 'routes/api.php',
            'views/index' => 'resources/views/index.blade.php',
            'views/master' => 'resources/views/components/layouts/master.blade.php',
            'scaffold/config' => 'config/config.php',
            'composer' => 'composer.json',
            'assets/js/app' => 'resources/assets/js/app.js',
            'assets/sass/app' => 'resources/assets/sass/app.scss',
            'vite' => 'vite.config.js',
            'package' => 'package.json',
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
            // 1. DOMAIN LAYER (Dùng Models làm Domain Model, không dùng Entities)
            // =================================================================
            'model'                => ['path' => 'Domain/Models', 'generate' => true],
            'vo'                   => ['path' => 'Domain/ValueObjects', 'generate' => true],
            'repository'           => ['path' => 'Domain/Repositories', 'generate' => true],
            'interfaces'           => ['path' => 'Domain/Contracts', 'generate' => false],
            'event'                => ['path' => 'Domain/Events', 'generate' => false],
            'enums'                => ['path' => 'Domain/Enums', 'generate' => false],
            'exceptions'           => ['path' => 'Domain/Exceptions', 'generate' => false],
            'rules'                => ['path' => 'Domain/Rules', 'generate' => false],
            'policies'             => ['path' => 'Domain/Policies', 'generate' => false],

            // =================================================================
            // 2. APPLICATION LAYER (Use Cases, Actions, DTOs, Listeners, Jobs)
            // =================================================================
            'usecases'             => ['path' => 'Application/UseCases', 'generate' => true],
            'actions'              => ['path' => 'Application/Actions', 'generate' => false],
            'services'             => ['path' => 'Application/Services', 'generate' => false],
            'listener'             => ['path' => 'Application/Listeners', 'generate' => false],
            'jobs'                 => ['path' => 'Application/Jobs', 'generate' => false],
            'job-middleware'       => ['path' => 'Application/Jobs/Middleware', 'generate' => false],
            'notifications'        => ['path' => 'Application/Notifications', 'generate' => false],
            'emails'               => ['path' => 'Application/Emails', 'generate' => false],

            // =================================================================
            // 3. INFRASTRUCTURE LAYER (Persistence, Providers, Config, DB)
            // =================================================================
            'mappers'              => ['path' => 'Infrastructure/Persistence/Mappers', 'generate' => false],
            'eloquent-repositories'=> ['path' => 'Infrastructure/Persistence/Repositories', 'generate' => true],
            'casts'                => ['path' => 'Infrastructure/Persistence/Casts', 'generate' => false],
            'scopes'               => ['path' => 'Infrastructure/Persistence/Scopes', 'generate' => false],
            'observer'             => ['path' => 'Infrastructure/Persistence/Observers', 'generate' => false],

            'provider'             => ['path' => 'Infrastructure/Providers', 'generate' => true],
            'route-provider'       => ['path' => 'Infrastructure/Providers', 'generate' => true],
            'command'              => ['path' => 'Infrastructure/Console', 'generate' => false],
            'command_replacements' => ['path' => 'Infrastructure/Console/Replacements', 'generate' => false],
            'channels'             => ['path' => 'Infrastructure/Broadcasting', 'generate' => false],
            'class'                => ['path' => 'Infrastructure/Classes', 'generate' => false],
            'helpers'              => ['path' => 'Infrastructure/Helpers', 'generate' => false],
            'traits'               => ['path' => 'Infrastructure/Traits', 'generate' => false],

            'config'               => ['path' => 'Infrastructure/Config', 'generate' => true],
            'factory'              => ['path' => 'Infrastructure/Database/Factories', 'generate' => true],
            'migration'            => ['path' => 'Infrastructure/Database/Migrations', 'generate' => true],
            'seeder'               => ['path' => 'Infrastructure/Database/Seeders', 'generate' => true],
            'lang'                 => ['path' => 'Infrastructure/Lang', 'generate' => false],

            // =================================================================
            // 4. INBOUND LAYER (REST API V1)
            // =================================================================
            'controller'           => ['path' => 'Inbound/Rest/V1', 'generate' => true],
            'request'              => ['path' => 'Inbound/Rest/V1/Requests', 'generate' => false],
            'resource'             => ['path' => 'Inbound/Rest/V1/Resources', 'generate' => false],
            'filter'               => ['path' => 'Inbound/Rest/V1/Middleware', 'generate' => false],
            'routes'               => ['path' => 'Inbound/Rest/V1/Routes', 'generate' => true],

            // UI / Views (Tắt mặc định cho API)
            'component-class'      => ['path' => 'Inbound/Web/Components', 'generate' => false],
            'component-view'       => ['path' => 'Inbound/Web/views/components', 'generate' => false],
            'views'                => ['path' => 'Inbound/Web/views', 'generate' => false],
            'assets'               => ['path' => 'Inbound/Web/assets', 'generate' => false],
            'inertia'              => ['path' => 'Inbound/Web/js/Pages', 'generate' => false],
            'inertia-components'   => ['path' => 'Inbound/Web/js/Components', 'generate' => false],

            // =================================================================
            // 5. TESTS
            // =================================================================
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
