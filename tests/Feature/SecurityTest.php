<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Orang Baru',
            'email' => 'baru@yaygroup.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'user',
        ], $overrides);
    }

    public function test_it_staff_cannot_create_super_admin(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);

        $this->actingAs($staff)->post(route('users.store'), $this->userPayload(['role' => 'super_admin']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'baru@yaygroup.id']);
    }

    public function test_it_staff_cannot_promote_self_to_super_admin(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);

        $this->actingAs($staff)->put(route('users.update', $staff), [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'super_admin',
        ]);

        $this->assertSame('it_staff', $staff->fresh()->role);
    }

    public function test_it_staff_cannot_edit_reset_or_delete_super_admin(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $admin = User::factory()->create(['role' => 'super_admin', 'password' => Hash::make('passwordAsli1')]);

        $this->actingAs($staff)->get(route('users.edit', $admin))->assertForbidden();
        $this->actingAs($staff)->put(route('users.update', $admin), [
            'name' => 'x', 'email' => $admin->email, 'role' => 'user',
            'password' => 'diambilalih1', 'password_confirmation' => 'diambilalih1',
        ])->assertForbidden();
        $this->actingAs($staff)->delete(route('users.destroy', $admin))->assertForbidden();

        $admin->refresh();
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue(Hash::check('passwordAsli1', $admin->password));
    }

    public function test_super_admin_can_still_manage_roles(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $staff = User::factory()->create(['role' => 'it_staff']);

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'super_admin',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('super_admin', $staff->fresh()->role);
    }

    public function test_it_staff_can_still_create_regular_user(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);

        $this->actingAs($staff)->post(route('users.store'), $this->userPayload())
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'baru@yaygroup.id', 'role' => 'user']);
    }

    public function test_regular_user_cannot_open_admin_pages(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        foreach (['users.index', 'assets.index', 'reports.index', 'agent-tokens.index', 'bast.index', 'stf.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_responses_have_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_public_sign_page_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/bast/sign/token-salah')->assertNotFound();
        }

        $this->get('/bast/sign/token-salah')->assertStatus(429);
    }
}
