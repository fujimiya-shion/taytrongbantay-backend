<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

/**
 * Sinh bộ ba Command + Output + UseCase cho một hành động không phải CRUD.
 *
 *   php artisan make:action PublishPost Identity
 *   php artisan make:action GetPostStatistics Identity --query
 */
final class MakeActionCommand extends ScaffoldCommand
{
    protected $signature = 'make:action
        {name : Tên hành động, động từ + đối tượng, StudlyCase (vd: PublishPost)}
        {module : Tên module đã tồn tại (vd: Identity)}
        {--query : Hành động dạng đọc, Command extends QueryCommand (có sẵn $criteria)}
        {--force : Ghi đè file đã tồn tại}';

    protected $description = 'Sinh Command, Output và UseCase cho một hành động trong module';

    public function handle(): int
    {
        $action = $this->validClassName((string) $this->argument('name'));
        if ($action === null || ! $this->resolveModule((string) $this->argument('module'))) {
            return self::FAILURE;
        }

        foreach (['Command', 'UseCase', 'Output'] as $suffix) {
            if (str_ends_with($action, $suffix)) {
                $this->components->error("Không thêm hậu tố [{$suffix}] vào tên, command tự thêm. Ví dụ: make:action PublishPost {$this->module->getName()}");

                return self::FAILURE;
            }
        }

        $this->force = (bool) $this->option('force');
        $isQuery = (bool) $this->option('query');

        $this->replacements = $this->namespaceReplacements() + [
            '{{ Action }}' => $action,
            '{{ commandBase }}' => $isQuery ? 'QueryCommand' : 'BaseCommand',
            '{{ commandBaseFqcn }}' => $isQuery ? 'App\Shared\Commands\QueryCommand' : 'App\Shared\Commands\BaseCommand',
            '{{ commandBody }}' => $isQuery
                ? '    // Có sẵn public QueryCriteria $criteria. Thêm tham số riêng thì khai báo constructor và gọi parent::__construct($criteria).'
                : "    public function __construct(\n        // TODO: input của hành động\n    ) {}",
        ];

        $this->components->info("Sinh action [{$action}] trong module [{$this->module->getName()}]");

        $this->write($this->pathFor('app-command')."/{$action}Command.php", $this->render('action.command'));
        $this->write($this->pathFor('app-output')."/{$action}Output.php", $this->render('action.output'));
        $this->write($this->pathFor('services')."/{$action}UseCase.php", $this->render('action.usecase'));

        $this->newLine();
        $this->line("  Controller gọi: \$useCase->execute(new {$action}Command(...))");

        return self::SUCCESS;
    }
}
