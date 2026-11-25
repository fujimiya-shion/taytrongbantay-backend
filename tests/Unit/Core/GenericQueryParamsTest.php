<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\DTO\BaseQueryParams;
use App\Core\DTO\GenericQueryParams;
use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

final class GenericQueryParamsTest extends TestCase
{
    private function sql(QueryCriteria $criteria): string
    {
        $model = new class extends Model
        {
            protected $table = 'items';
        };

        return $criteria->applyTo($model->newQuery(), $model)->toRawSql();
    }

    public function test_ids_from_query_string_become_where_in(): void
    {
        $sql = $this->sql(GenericQueryParams::fromArray(['ids' => '1,2,3'])->toCriteria());

        $this->assertStringContainsString("\"items\".\"id\" in ('1', '2', '3')", $sql);
    }

    public function test_search_uses_case_insensitive_like_and_escapes_wildcards(): void
    {
        $sql = $this->sql(GenericQueryParams::fromArray(['search' => '50%_off'])->toCriteria());

        $this->assertStringContainsString('"items"."name"::text ilike', $sql);
        $this->assertStringContainsString('50\\%\\_off', $sql);
    }

    public function test_date_range_covers_whole_days(): void
    {
        $sql = $this->sql(GenericQueryParams::fromArray(['fromDate' => '2026-01-01', 'toDate' => '2026-01-31'])->toCriteria());

        $this->assertStringContainsString("\"items\".\"created_at\" >= '2026-01-01 00:00:00'", $sql);
        $this->assertStringContainsString("\"items\".\"created_at\" <= '2026-01-31 23:59:59'", $sql);
    }

    public function test_invalid_date_is_ignored(): void
    {
        $sql = $this->sql(GenericQueryParams::fromArray(['fromDate' => 'not-a-date'])->toCriteria());

        $this->assertStringNotContainsString('created_at', $sql);
    }

    public function test_with_only_loads_whitelisted_relations(): void
    {
        $params = GenericQueryParams::fromArray(['with' => 'roles,secretStuff']);

        $this->assertNull($this->eagerLoads($params->toCriteria()));
        $this->assertSame(['roles'], $this->eagerLoads($params->toCriteria(allowedRelations: ['roles'])));
    }

    public function test_cursor_is_not_treated_as_a_filter(): void
    {
        $params = new class extends BaseQueryParams
        {
            public ?string $status = null;
        };
        $params = $params::fromArray(['cursor' => 'abc', 'status' => 'active']);

        $sql = $this->sql($params->toCriteria());

        $this->assertStringContainsString("\"items\".\"status\" = 'active'", $sql);
        $this->assertStringNotContainsString('cursor', $sql);
    }

    /**
     * @return list<string>|null
     */
    private function eagerLoads(QueryCriteria $criteria): ?array
    {
        $model = new class extends Model
        {
            protected $table = 'items';
        };
        $loads = array_keys($criteria->applyTo($model->newQuery(), $model)->getEagerLoads());

        return $loads === [] ? null : $loads;
    }
}
