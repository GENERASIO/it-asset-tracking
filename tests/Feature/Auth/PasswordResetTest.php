<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_is_disabled(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'x@example.com'])->assertNotFound();
        $this->get('/reset-password/token')->assertNotFound();
    }

    public function test_login_page_has_no_forgot_password_link(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Forgot your password?');
    }
}
