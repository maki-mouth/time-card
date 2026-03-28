<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_email_is_required_for_login()
    {
        $response = $this->from('/admin/login')->post('/login', [
            'email' => '',
            'password' => 'admin_password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_password_is_required_for_login()
    {
        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_login_fails_with_invalid_credentials()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('correct_admin_pass'),
            'role' => 'admin',
        ]);

        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong_pass',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }
}