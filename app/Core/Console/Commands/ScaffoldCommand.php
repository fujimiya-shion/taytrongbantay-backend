<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module as ModuleRegistry;
use Nwidart\Modules\Module;

/**
 * Hàm dùng chung cho make:feature và make:action.
 *
 * Đường dẫn và namespace đọc từ config/modules.php (paths.generator), nên đổi
 * cấu trúc module ở config là command tự đi theo. Template nằm ở stubs/feature/.
 */
abstract class ScaffoldCommand extends Command
{
    protected Module $module;

    protected bool $force = false;

    /** @var array<string, string> */
    protected array $replacements = [];

    public function __construct(protected readonly Filesystem $files)
    {
        parent::__construct();
    }

    protected function resolveModule(string $name): bool
    {
        $module = ModuleRegistry::find($name);

        if ($module === null) {
            $this->components->error("Module [{$name}] chưa tồn tại. Tạo trước bằng: php artisan module:make {$name} --api");

            return false;
        }

        if (! $module->isEnabled()) {
            $this->components->warn("Module [{$module->getName()}] đang bị tắt, route sẽ chưa hoạt động. Bật bằng: php artisan module:enable {$module->getName()}");
        }

        $this->module = $module;

        return true;
    }

    protected function validClassName(string $name): ?string
    {
        $studly = Str::studly($name);

        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $studly) !== 1) {
            $this->components->error("Tên [{$name}] không hợp lệ. Dùng StudlyCase, chỉ chữ và số, ví dụ: Post, BlogPost.");

            return null;
        }

        return $studly;
    }

    /**
     * Thư mục tuyệt đối của một generator key, ví dụ 'model' -> app/Modules/Identity/Domain.
     */
    protected function pathFor(string $key): string
    {
        $relative = config("modules.paths.generator.{$key}.path")
            ?? throw new \LogicException("Thiếu modules.paths.generator.{$key}.path trong config/modules.php");

        return $this->module->getPath().'/'.trim($relative, '/');
    }

    protected function namespaceFor(string $key): string
    {
        $relative = trim((string) config("modules.paths.generator.{$key}.path"), '/');

        return trim(config('modules.namespace'), '\\')
            .'\\'.$this->module->getName()
            .($relative === '' ? '' : '\\'.str_replace('/', '\\', $relative));
    }

    /**
     * Placeholder namespace dùng chung trong mọi stub: {{ ns.model }}, {{ ns.command }}...
     *
     * @return array<string, string>
     */
    protected function namespaceReplacements(): array
    {
        $map = [
            'model' => 'model',
            'repository' => 'repository',
            'migration' => 'migration',
            'command' => 'app-command',
            'output' => 'app-output',
            'usecase' => 'services',
            'request' => 'request',
            'resource' => 'resource',
            'controller' => 'controller',
            'test' => 'test-feature',
        ];

        $replacements = [];
        foreach ($map as $placeholder => $key) {
            $replacements["{{ ns.{$placeholder} }}"] = $this->namespaceFor($key);
        }

        return $replacements;
    }

    /**
     * @param  array<string, string>  $extra
     */
    protected function render(string $stub, array $extra = []): string
    {
        $path = base_path("stubs/feature/{$stub}.stub");

        return strtr($this->files->get($path), $extra + $this->replacements);
    }

    protected function write(string $path, string $contents): void
    {
        $relative = Str::after($path, base_path().'/');

        if ($this->files->exists($path) && ! $this->force) {
            $this->components->twoColumnDetail($relative, '<fg=yellow;options=bold>BỎ QUA (đã có)</>');

            return;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        $this->components->twoColumnDetail($relative, '<fg=green;options=bold>ĐÃ TẠO</>');
    }
}
