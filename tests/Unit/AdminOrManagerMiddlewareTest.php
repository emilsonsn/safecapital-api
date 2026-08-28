<?php

namespace Tests\Unit;

use App\Enums\UserRoleEnum;
use App\Http\Middleware\AdminOrManagerMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminOrManagerMiddlewareTest extends TestCase
{
    /** @dataProvider allowedRoles */
    public function test_allows_admin_and_manager(UserRoleEnum $role): void
    {
        $user = new User();
        $user->role = $role->value;
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = (new AdminOrManagerMiddleware())->handle(
            Request::create('/api/term', 'POST'),
            fn () => new Response(status: 204)
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_rejects_client(): void
    {
        $user = new User();
        $user->role = UserRoleEnum::Client->value;
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = (new AdminOrManagerMiddleware())->handle(
            Request::create('/api/term', 'POST'),
            fn () => new Response(status: 204)
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public static function allowedRoles(): array
    {
        return [
            'admin' => [UserRoleEnum::Admin],
            'manager' => [UserRoleEnum::Manager],
        ];
    }
}
