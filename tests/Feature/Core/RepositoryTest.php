<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Model\User;
use App\Core\Support\Criteria\QueryCriteria;
use App\Module\Admin\Domain\Port\Out\Persistence\AdminRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cursor_paginate_walks_through_all_records(): void
    {
        $admins = app(AdminRepositoryInterface::class);
        foreach (['a', 'b', 'c'] as $name) {
            $admins->create(['name' => $name, 'email' => "{$name}@example.test", 'password' => 'secret']);
        }

        $first = $admins->cursorPaginate(QueryCriteria::make()->perPage(2));
        $this->assertCount(2, $first->items());
        $this->assertNotNull($first->nextCursor());

        $second = $admins->cursorPaginate(QueryCriteria::make()->cursor($first->nextCursor()?->encode(), 2));
        $this->assertCount(1, $second->items());
        $this->assertNull($second->nextCursor());
    }

    public function test_user_factory_resolves_for_core_model(): void
    {
        $this->assertInstanceOf(User::class, User::factory()->make());
    }
}
