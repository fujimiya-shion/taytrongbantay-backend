<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Exception\BusinessException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class BusinessExceptionRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/__test/business-default', fn () => throw new class('Vi phạm quy tắc') extends BusinessException {});

        Route::get('/api/__test/business-conflict', fn () => throw new class('Email đã tồn tại', ['email' => ['Email đã tồn tại.']]) extends BusinessException
        {
            public function status(): int
            {
                return 409;
            }
        });
    }

    public function test_business_exception_renders_json_with_default_status(): void
    {
        $this->getJson('/api/__test/business-default')
            ->assertStatus(422)
            ->assertExactJson([
                'success' => false,
                'code' => 422,
                'message' => 'Vi phạm quy tắc',
                'errors' => [],
            ]);
    }

    public function test_business_exception_can_override_status_and_errors(): void
    {
        $this->getJson('/api/__test/business-conflict')
            ->assertStatus(409)
            ->assertJsonPath('code', 409)
            ->assertJsonPath('errors.email.0', 'Email đã tồn tại.');
    }
}
