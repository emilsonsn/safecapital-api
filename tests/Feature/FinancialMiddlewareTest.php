<?php

namespace Tests\Feature;

use App\Http\Middleware\FinancialMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FinancialMiddlewareTest extends TestCase
{
    public function test_admin_and_manager_can_access_financial_routes_but_client_cannot(): void
    {
        $middleware = app(FinancialMiddleware::class);

        Auth::shouldReceive('user')->once()->andReturn(new User(['role' => 'Admin']));
        $this->assertSame(204, $middleware->handle(Request::create('/'), fn () => response()->noContent())->getStatusCode());

        Auth::shouldReceive('user')->once()->andReturn(new User(['role' => 'Manager']));
        $this->assertSame(204, $middleware->handle(Request::create('/'), fn () => response()->noContent())->getStatusCode());

        Auth::shouldReceive('user')->once()->andReturn(new User(['role' => 'Client']));
        $this->assertSame(403, $middleware->handle(Request::create('/'), fn () => response()->noContent())->getStatusCode());
    }
}
