<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class QueryCriteriaTest extends TestCase
{
    private function sql(QueryCriteria $criteria): string
    {
        $model = new class extends Model
        {
            protected $table = 'items';
        };

        return $criteria->applyTo($model->newQuery(), $model)->toRawSql();
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function arrayConditions(): array
    {
        return [
            'chuỗi số từ query string' => [['1', '2', '3'], "\"items\".\"id\" in ('1', '2', '3')"],
            'số nguyên' => [[1, 2, 3], '"items"."id" in (1, 2, 3)'],
            'hai chuỗi không phải operator' => [['active', 'pending'], "\"items\".\"id\" in ('active', 'pending')"],
            'một phần tử' => [['7'], "\"items\".\"id\" in ('7')"],
            'tuple operator' => [['>=', 18], '"items"."id" >= 18'],
            'tuple between' => [['between', [1, 10]], '"items"."id" between 1 and 10'],
            'tuple not in' => [['not in', [4, 5]], '"items"."id" not in (4, 5)'],
        ];
    }

    #[DataProvider('arrayConditions')]
    public function test_array_condition(mixed $value, string $expected): void
    {
        $this->assertStringContainsString($expected, $this->sql(QueryCriteria::make()->where(['id' => $value])));
    }

    public function test_ilike_is_case_insensitive_like(): void
    {
        $sql = $this->sql(QueryCriteria::make()->where('name', 'ilike', '%an%'));

        $this->assertStringContainsString('"items"."name"::text ilike \'%an%\'', $sql);
    }

    public function test_where_is_immutable(): void
    {
        $base = QueryCriteria::make()->where('status', 'active');
        $base->where('role', 'admin');

        $this->assertStringNotContainsString('role', $this->sql($base));
    }
}
