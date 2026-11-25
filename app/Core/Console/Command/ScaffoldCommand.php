<?php

declare(strict_types=1);

namespace App\Core\Console\Command;

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
     * Thư mục tuyệt đối của một generator key, ví dụ 'model' -> app/Module/Identity/Domain.
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
            'repositoryInterface' => 'repository-interface',
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

        if (str_ends_with($path, '.php')) {
            $contents = $this->sortUseStatements($contents);
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        $this->components->twoColumnDetail($relative, '<fg=green;options=bold>ĐÃ TẠO</>');
    }

    /**
     * Sắp xếp khối `use` ở đầu file theo alphabet (khớp rule ordered_imports của Pint),
     * có thể thêm các class mới vào khối đó. Thứ tự phụ thuộc tên entity nên không
     * thể sắp sẵn trong stub.
     *
     * @param  list<string>  $add  FQCN cần thêm
     */
    protected function sortUseStatements(string $contents, array $add = []): string
    {
        preg_match_all('/^use ([^;\s]+);\n/m', $contents, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[0] === []) {
            if ($add === []) {
                return $contents;
            }

            $block = implode('', array_map(fn (string $class) => "use {$class};\n", $add));

            return (string) preg_replace('/^<\?php\s*/', "<?php\n\n{$block}\n", $contents, 1);
        }

        $classes = array_values(array_unique([...array_column($matches[1], 0), ...$add]));
        usort($classes, 'strcasecmp');

        $start = $matches[0][0][1];
        $last = end($matches[0]);
        $end = $last[1] + strlen($last[0]);

        // Chỉ xử lý khi các câu use nằm liền nhau; có dòng khác xen giữa thì giữ nguyên file.
        if (trim((string) preg_replace('/^use [^;\s]+;\n/m', '', substr($contents, $start, $end - $start))) !== '') {
            return $contents;
        }

        $block = implode('', array_map(fn (string $class) => "use {$class};\n", $classes));

        return substr($contents, 0, $start).$block.substr($contents, $end);
    }
}
