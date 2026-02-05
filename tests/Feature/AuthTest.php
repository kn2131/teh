<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_logout_with_token(): void
    {
        $password = 'secret';

        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $loginResponse->assertOk()->assertJsonStructure([
            'token',
        ]);

        $token = $loginResponse->json('token');

        $this->assertNotEmpty($token);
        $this->assertSame(1, PersonalAccessToken::count());

        $logoutResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $logoutResponse->assertOk()->assertJson([
            'message' => 'Logged out',
        ]);

        $this->assertSame(0, PersonalAccessToken::count());
    }
}
