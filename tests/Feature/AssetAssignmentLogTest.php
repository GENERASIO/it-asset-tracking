<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetAssignmentLogTest extends TestCase
{
    use RefreshDatabase;

    private function asset(?int $assignedTo = null): Asset
    {
        return Asset::create([
            'asset_code' => 'LTP-HO-0001',
            'name' => 'Dell Vostro 3400',
            'category_id' => Category::create(['name' => 'Laptop', 'code' => 'LTP'])->id,
            'location_id' => Location::create(['name' => 'Head Office', 'code' => 'HO'])->id,
            'status' => 'in_use',
            'serial_number' => 'SN-1',
            'assigned_to' => $assignedTo,
        ]);
    }

    private function payload(Asset $asset, array $override = []): array
    {
        return array_merge([
            'name' => $asset->name,
            'category_id' => $asset->category_id,
            'location_id' => $asset->location_id,
            'status' => $asset->status,
        ], $override);
    }

    public function test_ganti_pemegang_tercatat_di_aktivitas(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $dasep = User::factory()->create(['name' => 'Dasep']);
        $ayu = User::factory()->create(['name' => 'Ayu Natali']);

        $asset = $this->asset($dasep->id);

        $this->actingAs($staff)
            ->put(route('assets.update', $asset), $this->payload($asset, ['assigned_to' => $ayu->id]))
            ->assertRedirect();

        $log = AssetLog::where('asset_id', $asset->id)->where('action', 'assignment_change')->first();

        $this->assertNotNull($log);
        $this->assertSame($dasep->id, $log->from_user_id);
        $this->assertSame($ayu->id, $log->to_user_id);
        $this->assertSame($staff->id, $log->handled_by);
    }

    public function test_tidak_mencatat_kalau_pemegang_tidak_berubah(): void
    {
        $staff = User::factory()->create(['role' => 'it_staff']);
        $dasep = User::factory()->create(['name' => 'Dasep']);

        $asset = $this->asset($dasep->id);

        $this->actingAs($staff)
            ->put(route('assets.update', $asset), $this->payload($asset, ['assigned_to' => $dasep->id, 'name' => 'Nama Baru']))
            ->assertRedirect();

        $this->assertDatabaseMissing('asset_logs', [
            'asset_id' => $asset->id,
            'action' => 'assignment_change',
        ]);
    }
}
