<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_staff_bisa_akses_semua_fitur(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);

        // Halaman yang dulu khusus super_admin, kini terbuka untuk IT.
        foreach (['categories.index', 'locations.index', 'users.index', 'agent-tokens.index', 'software-licenses.index'] as $route) {
            $this->actingAs($staff)->get(route($route))->assertOk();
        }

        // Menu di sidebar juga muncul untuk IT.
        $this->actingAs($staff)->get(route('software-licenses.index'))
            ->assertSee('href="'.route('agent-tokens.index').'"', false)
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertSee('href="'.route('categories.index').'"', false);
    }

    public function test_role_user_biasa_tetap_tidak_bisa_akses(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        foreach (['categories.index', 'users.index', 'agent-tokens.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_laporan_ada_di_urutan_paling_bawah_sidebar(): void
    {
        $href = fn ($route) => 'href="'.route($route).'"';

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('software-licenses.index'))
            ->assertSeeInOrder([
                $href('assets.index'), $href('users.index'), $href('software-licenses.index'),
                $href('agent-tokens.index'), $href('reports.index'), '</nav>',
            ], false);

        $this->actingAs(User::factory()->create(['role' => 'it_staff']))
            ->get(route('software-licenses.index'))
            ->assertSeeInOrder([
                $href('assets.index'), $href('users.index'), $href('software-licenses.index'),
                $href('agent-tokens.index'), $href('reports.index'), '</nav>',
            ], false);
    }

    public function test_halaman_kelola_user_tidak_lagi_punya_tombol_agent(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Kelola Agent Token');
    }
}
