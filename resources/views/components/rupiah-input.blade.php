{{--
    Input nominal Rupiah: ketik 10000000 -> tampil "Rp 10.000.000". Selain angka diabaikan.
    Yang terkirim tetap teks berformat; server membuang semua karakter non-angka
    (AssetController::normalizeRupiah), jadi tetap aman walaupun JavaScript tidak jalan.
--}}
@props(['name', 'value' => null])

@php
    if ($value === null || $value === '') {
        $digits = '';
    } elseif (is_numeric($value)) {
        $digits = (string) (int) round((float) $value);
    } else {
        $digits = preg_replace('/\D/', '', (string) $value);
    }
    $digits = ltrim($digits, '0');
@endphp

<input type="text" inputmode="numeric" autocomplete="off" name="{{ $name }}" placeholder="Rp 0"
       value="{{ $digits !== '' ? 'Rp '.number_format((int) $digits, 0, ',', '.') : '' }}"
       x-data
       x-on:input="
           const digits = $el.value.replace(/\D/g, '').replace(/^0+/, '');
           $el.value = digits ? 'Rp ' + digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
           $el.setSelectionRange($el.value.length, $el.value.length);
       "
       {{ $attributes }}>
