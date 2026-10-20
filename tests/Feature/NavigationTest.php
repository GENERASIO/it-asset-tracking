<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_agent_hanya_untuk_super_admin(): void
    {
        $agentUrl = route('agent-tokens.index');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('software-licenses.index'))
            ->assertOk()
            ->assertSee('href="'.$agentUrl.'"', false);

        $this->actingAs(User::factory()->create(['role' => 'it_staff']))
            ->get(route('software-licenses.index'))
            ->assertOk()
            ->assertDontSee('href="'.$agentUrl.'"', false);
    }

    public function test_halaman_kelola_user_tidak_lagi_punya_tombol_agent(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Kelola Agent Token');
    }
}
