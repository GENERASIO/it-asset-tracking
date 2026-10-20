@php
    $input = 'w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm';
    $label = 'block text-sm text-gray-600 dark:text-gray-300 mb-1';
@endphp

@if ($errors->any())
    <div class="mb-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
        <ul class="list-disc ml-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="{{ $label }}">Nama lisensi</label>
        <input type="text" name="name" required class="{{ $input }}" placeholder="mis. Office Home & Business 2021"
               value="{{ old('name', $entitlement?->name) }}">
    </div>
    <div>
        <label class="{{ $label }}">Kata kunci pencocokan</label>
        <input type="text" name="match" required class="{{ $input }} font-mono" placeholder="mis. HomeBusiness2021"
               value="{{ old('match', $entitlement?->match) }}">
        <p class="text-xs text-gray-500 mt-1">Lihat tabel "Edisi Terdeteksi" di bawah.</p>
    </div>
    <div>
        <label class="{{ $label }}">Jenis</label>
        <select name="license_type" class="{{ $input }}">
            @foreach (\App\Models\SoftwareEntitlement::TYPES as $value => $text)
                <option value="{{ $value }}" @selected(old('license_type', $entitlement?->license_type ?? 'perpetual') === $value)>{{ $text }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $label }}">Jumlah dibeli</label>
        <input type="number" name="quantity" min="0" required class="{{ $input }}"
               value="{{ old('quantity', $entitlement?->quantity) }}">
    </div>
    <div>
        <label class="{{ $label }}">Berlaku sampai <span class="text-gray-400">(wajib untuk langganan)</span></label>
        <input type="date" name="expires_at" class="{{ $input }}"
               value="{{ old('expires_at', $entitlement?->expires_at?->format('Y-m-d')) }}">
    </div>
    <div>
        <label class="{{ $label }}">Catatan</label>
        <input type="text" name="notes" class="{{ $input }}" placeholder="mis. no. invoice, vendor"
               value="{{ old('notes', $entitlement?->notes) }}">
    </div>
</div>
