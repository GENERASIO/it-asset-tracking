<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\PriceReference;
use Illuminate\Http\Request;

class PriceReferenceController extends Controller
{
    public function index()
    {
        $references = PriceReference::orderBy('match_name')->get();

        // Berapa unit yang cocok dengan tiap entri, biar IT tahu dampaknya.
        $counts = Asset::selectRaw('name, COUNT(*) as c')->groupBy('name')->pluck('c', 'name');

        // Model yang terdeteksi agent tapi belum punya harga sama sekali (saran untuk diisi).
        $missing = Asset::whereNull('purchase_price')
            ->selectRaw('name, COUNT(*) as unit')
            ->groupBy('name')
            ->orderByDesc('unit')
            ->get()
            ->reject(fn ($row) => PriceReference::where('match_name', $row->name)->exists());

        return view('price-references.index', compact('references', 'counts', 'missing'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        PriceReference::updateOrCreate(
            ['match_name' => $data['match_name']],
            ['purchase_price' => $data['purchase_price'], 'note' => $data['note'] ?? null]
        );

        // Langsung terapkan ke unit yang modelnya cocok tapi harganya masih kosong.
        $applied = Asset::where('name', $data['match_name'])
            ->whereNull('purchase_price')
            ->update(['purchase_price' => $data['purchase_price'], 'purchase_price_estimated' => false]);

        return back()->with('success', "Harga model disimpan. $applied unit tanpa harga langsung terisi.");
    }

    public function update(Request $request, PriceReference $priceReference)
    {
        $data = $this->validated($request, $priceReference->id);

        $priceReference->update([
            'match_name' => $data['match_name'],
            'purchase_price' => $data['purchase_price'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'Harga model diperbarui.');
    }

    public function destroy(PriceReference $priceReference)
    {
        $priceReference->delete();

        return back()->with('success', 'Harga model dihapus. Harga aset yang sudah terisi tidak berubah.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        // Input rupiah dikirim sebagai teks berformat ("Rp 10.000.000"); ambil angkanya saja.
        $request->merge([
            'purchase_price' => preg_replace('/\D/', '', (string) $request->input('purchase_price')),
        ]);

        return $request->validate([
            'match_name' => 'required|string|max:191|unique:price_references,match_name'.($ignoreId ? ",$ignoreId" : ''),
            'purchase_price' => 'required|integer|min:1000|max:999999999999',
            'note' => 'nullable|string|max:191',
        ]);
    }
}
