<?php

declare(strict_types=1);

namespace App\Core\Console\Command;

use Illuminate\Support\Str;

/**
 * Sinh bộ khung CRUD cho một entity trong module, đúng cấu trúc trong AGENTS.md.
 *
 *   php artisan make:feature Post Identity
 *   php artisan make:feature Post Identity --actions=list,show
 */
final class MakeFeatureCommand extends ScaffoldCommand
{
    private const ACTIONS = ['list', 'show', 'create', 'update', 'delete'];

    protected $signature = 'make:feature
        {name : Tên entity, số ít, StudlyCase (vd: Post, BlogPost)}
        {module : Tên module đã tồn tại (vd: Identity)}
        {--actions=list,show,create,update,delete : Các action cần sinh, phân tách bằng dấu phẩy}
        {--force : Ghi đè file đã tồn tại}';

    protected $description = 'Sinh bộ khung CRUD cho một entity: model, migration, repository, command, output, use case, request, resource, controller, route, test';

    public function handle(): int
    {
        $name = $this->validClassName((string) $this->argument('name'));
        if ($name === null || ! $this->resolveModule((string) $this->argument('module'))) {
            return self::FAILURE;
        }

        $actions = $this->parseActions();
        if ($actions === null) {
            return self::FAILURE;
        }

        $this->force = (bool) $this->option('force');
        $plural = Str::pluralStudly($name);

        $this->replacements = $this->namespaceReplacements() + [
            '{{ Name }}' => $name,
            '{{ name }}' => Str::camel($name),
            '{{ names }}' => Str::camel($plural),
            '{{ table }}' => Str::snake($plural),
            '{{ uri }}' => Str::kebab($plural),
            '{{ moduleLower }}' => $this->module->getLowerName(),
        ];

        $this->components->info("Sinh feature [{$name}] trong module [{$this->module->getName()}]: ".implode(', ', $actions));

        $this->write($this->pathFor('model')."/{$name}.php", $this->render('model'));
        $this->writeMigration();
        $this->write($this->pathFor('repository-interface')."/{$name}RepositoryInterface.php", $this->render('repository-interface'));
        $this->write($this->pathFor('repository')."/{$name}Repository.php", $this->render('repository'));
        $this->bindRepository($name);

        if (array_intersect($actions, ['list', 'show', 'create', 'update']) !== []) {
            $this->write($this->pathFor('resource')."/{$name}Resource.php", $this->render('resource'));
        }

        foreach ($actions as $action) {
            $this->writeAction($action, $name);
        }

        $this->writeController($actions, $name);
        $this->appendRoutes($actions, $name);
        $this->writeTest($actions, $name);

        $this->printNextSteps($name);

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function parseActions(): ?array
    {
        $actions = array_values(array_unique(array_filter(array_map(
            'trim',
            explode(',', strtolower((string) $this->option('actions'))),
        ))));

        $invalid = array_diff($actions, self::ACTIONS);
        if ($invalid !== [] || $actions === []) {
            $this->components->error('Action không hợp lệ: '.implode(', ', $invalid ?: ['(trống)']).'. Chỉ nhận: '.implode(', ', self::ACTIONS));

            return null;
        }

        // Giữ thứ tự chuẩn để controller, route, test luôn sắp xếp giống nhau.
        return array_values(array_intersect(self::ACTIONS, $actions));
    }

    private function writeMigration(): void
    {
        $table = $this->replacements['{{ table }}'];
        $dir = $this->pathFor('migration');
        $existing = $this->files->glob("{$dir}/*_create_{$table}_table.php");

        if ($existing !== [] && ! $this->force) {
            $this->components->twoColumnDetail(Str::after($existing[0], base_path().'/'), '<fg=yellow;options=bold>BỎ QUA (đã có)</>');

            return;
        }

        $path = $existing[0] ?? $dir.'/'.date('Y_m_d_His')."_create_{$table}_table.php";
        $this->write($path, $this->render('migration'));
    }

    private function writeAction(string $action, string $name): void
    {
        [$command, $output, $useCase] = match ($action) {
            'list' => ["Get{$name}ListCommand", "Get{$name}ListOutput", "Get{$name}ListUseCase"],
            'show' => ["Get{$name}DetailCommand", "Get{$name}DetailOutput", "Get{$name}DetailUseCase"],
            'create' => ["Create{$name}Command", "Create{$name}Output", "Create{$name}UseCase"],
            'update' => ["Update{$name}Command", "Update{$name}Output", "Update{$name}UseCase"],
            'delete' => ["Delete{$name}Command", null, "Delete{$name}UseCase"],
        };

        $this->write($this->pathFor('app-command')."/{$command}.php", $this->render("command.{$action}"));

        if ($output !== null) {
            $this->write($this->pathFor('app-output')."/{$output}.php", $this->render("output.{$action}"));
        }

        $this->write($this->pathFor('services')."/{$useCase}.php", $this->render("usecase.{$action}"));

        if (in_array($action, ['create', 'update'], true)) {
            $this->write($this->pathFor('request').'/'.ucfirst($action)."{$name}Request.php", $this->render("request.{$action}"));
        }
    }

    /**
     * @param  list<string>  $actions
     */
    private function writeController(array $actions, string $name): void
    {
        $ns = fn (string $key, string $class) => $this->replacements["{{ ns.{$key} }}"].'\\'.$class;

        $uses = [
            'App\Core\Http\Controller\ApiController',
            'Illuminate\Http\JsonResponse',
        ];

        if (array_intersect($actions, ['list', 'show', 'create', 'update']) !== []) {
            $uses[] = $ns('resource', "{$name}Resource");
        }

        foreach ($actions as $action) {
            array_push($uses, ...match ($action) {
                'list' => [
                    'App\Core\DTO\GenericQueryParams',
                    'Illuminate\Http\Request',
                    $ns('model', $name),
                    $ns('command', "Get{$name}ListCommand"),
                    $ns('usecase', "Get{$name}ListUseCase"),
                ],
                'show' => [
                    $ns('command', "Get{$name}DetailCommand"),
                    $ns('usecase', "Get{$name}DetailUseCase"),
                ],
                'create' => [
                    $ns('request', "Create{$name}Request"),
                    $ns('usecase', "Create{$name}UseCase"),
                ],
                'update' => [
                    $ns('request', "Update{$name}Request"),
                    $ns('usecase', "Update{$name}UseCase"),
                ],
                'delete' => [
                    $ns('command', "Delete{$name}Command"),
                    $ns('usecase', "Delete{$name}UseCase"),
                ],
            });
        }

        $uses = array_unique($uses);
        sort($uses);

        $methods = array_map(fn (string $action) => rtrim($this->render("controller.{$action}")), $actions);

        $this->write(
            $this->pathFor('controller')."/{$name}Controller.php",
            $this->render('controller', [
                '{{ uses }}' => implode("\n", array_map(fn (string $class) => "use {$class};", $uses)),
                '{{ methods }}' => implode("\n\n", $methods),
            ]),
        );
    }

    /**
     * Thêm một nhóm route mới vào cuối file route của module, kèm câu `use` controller.
     *
     * @param  list<string>  $actions
     */
    private function appendRoutes(array $actions, string $name): void
    {
        $file = $this->module->getPath().'/'.ltrim((string) config('modules.stubs.files.routes/api'), '/');
        $relative = Str::after($file, base_path().'/');

        if (! $this->files->exists($file)) {
            $this->components->twoColumnDetail($relative, '<fg=red;options=bold>KHÔNG TÌM THẤY, tự thêm route</>');

            return;
        }

        $controller = "{$name}Controller";
        $fqcn = $this->replacements['{{ ns.controller }}'].'\\'.$controller;
        $contents = $this->files->get($file);

        if (str_contains($contents, "{$controller}::class")) {
            $this->components->twoColumnDetail($relative, '<fg=yellow;options=bold>BỎ QUA (đã có route)</>');

            return;
        }

        $uri = $this->replacements['{{ uri }}'];
        $routes = array_map(fn (string $action) => match ($action) {
            'list' => "    Route::get('{$uri}', [{$controller}::class, 'index'])->name('{$uri}.index');",
            'show' => "    Route::get('{$uri}/{id}', [{$controller}::class, 'show'])->whereNumber('id')->name('{$uri}.show');",
            'create' => "    Route::post('{$uri}', [{$controller}::class, 'store'])->name('{$uri}.store');",
            'update' => "    Route::put('{$uri}/{id}', [{$controller}::class, 'update'])->whereNumber('id')->name('{$uri}.update');",
            'delete' => "    Route::delete('{$uri}/{id}', [{$controller}::class, 'destroy'])->whereNumber('id')->name('{$uri}.destroy');",
        }, $actions);

        $contents = $this->sortUseStatements($contents, [$fqcn]);
        $contents = rtrim($contents)."\n".$this->render('routes', ['{{ routes }}' => implode("\n", $routes)]);

        $this->files->put($file, $contents);
        $this->components->twoColumnDetail($relative, '<fg=green;options=bold>ĐÃ THÊM ROUTE</>');
    }

    /**
     * Thêm binding <Name>RepositoryInterface => <Name>Repository vào ServiceProvider chính
     * của module: chèn vào mảng $bindings nếu đã có, chưa có thì tạo property mới.
     */
    private function bindRepository(string $name): void
    {
        $module = $this->module->getName();
        $file = $this->pathFor('provider')."/{$module}ServiceProvider.php";
        $relative = Str::after($file, base_path().'/');

        $interface = "{$name}RepositoryInterface";
        $implementation = "{$name}Repository";
        $entry = "{$interface}::class => {$implementation}::class,";
        $manual = "tự thêm vào \$bindings: {$entry}";

        if (! $this->files->exists($file)) {
            $this->components->twoColumnDetail($relative, "<fg=red;options=bold>KHÔNG TÌM THẤY, {$manual}</>");

            return;
        }

        $contents = $this->files->get($file);

        if (str_contains($contents, "{$interface}::class")) {
            $this->components->twoColumnDetail($relative, '<fg=yellow;options=bold>BỎ QUA (đã có binding)</>');

            return;
        }

        $updated = preg_match('/(\$bindings\s*=\s*\[)(.*?)(\n?[ \t]*\];)/s', $contents) === 1
            // Đã có $bindings: thêm dòng mới ngay trước dấu "];" đóng mảng.
            ? (string) preg_replace_callback(
                '/(\$bindings\s*=\s*\[)(.*?)(\n?[ \t]*\];)/s',
                fn (array $m) => $m[1].rtrim($m[2])."\n        {$entry}\n    ];",
                $contents,
                1,
            )
            // Chưa có: tạo property ngay sau dấu "{" mở class.
            : (string) preg_replace(
                '/(class\s+'.preg_quote("{$module}ServiceProvider", '/').'\b[^{]*\{\n)/',
                "\$1    /**\n     * @var array<class-string, class-string>\n     */\n    public array \$bindings = [\n        {$entry}\n    ];\n\n",
                $contents,
                1,
            );

        if ($updated === $contents) {
            $this->components->twoColumnDetail($relative, "<fg=red;options=bold>KHÔNG CHÈN ĐƯỢC, {$manual}</>");

            return;
        }

        $interfaceFqcn = $this->replacements['{{ ns.repositoryInterface }}'].'\\'.$interface;
        $implementationFqcn = $this->replacements['{{ ns.repository }}'].'\\'.$implementation;

        $withUses = $this->sortUseStatements($updated, [$interfaceFqcn, $implementationFqcn]);

        // Không chèn được `use` (khối use không liền nhau...) thì ghi tên đầy đủ để file vẫn chạy đúng.
        $updated = str_contains($withUses, "use {$interfaceFqcn};") && str_contains($withUses, "use {$implementationFqcn};")
            ? $withUses
            : str_replace($entry, "\\{$interfaceFqcn}::class => \\{$implementationFqcn}::class,", $updated);

        $this->files->put($file, $updated);
        $this->components->twoColumnDetail($relative, '<fg=green;options=bold>ĐÃ THÊM BINDING</>');
    }

    /**
     * @param  list<string>  $actions
     */
    private function writeTest(array $actions, string $name): void
    {
        $methods = array_map(fn (string $action) => rtrim($this->render("test.{$action}")), $actions);

        $this->write(
            $this->pathFor('test-feature')."/{$name}ApiTest.php",
            $this->render('test', ['{{ methods }}' => implode("\n", $methods)]),
        );
    }

    private function printNextSteps(string $name): void
    {
        $module = $this->module->getName();

        $this->newLine();
        $this->components->info('Việc cần làm tiếp (tìm "TODO" trong các file vừa tạo):');
        $this->components->bulletList([
            'Migration: thêm cột, rồi chạy php artisan migrate',
            "Domain/Model/{$name}.php: khai báo \$fillable, \$hidden, quan hệ",
            'Command + FormRequest: khai báo field và rules() (key phải trùng tên tham số Command)',
            "{$name}RepositoryInterface: khai báo method riêng (nếu cần), cài đặt trong {$name}Repository",
            'UseCase: map field từ Command sang mảng create()/update(), thêm logic nghiệp vụ',
            "{$name}Resource: chọn field trả ra JSON; Controller: chỉnh allowedSorts",
            "Chạy test: php artisan test --filter={$name}ApiTest",
        ]);
        $this->line("  Route: php artisan route:list --path=api/v1/{$this->replacements['{{ uri }}']}  (module {$module})");
    }
}
