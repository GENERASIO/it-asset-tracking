<?php

return [
    // Server KMS resmi milik perusahaan (pisahkan dengan koma). Aktivasi KMS ke server lain
    // ditandai "terindikasi tidak resmi". Kosongkan kalau perusahaan tidak punya Volume License.
    'allowed_kms_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('LICENSE_ALLOWED_KMS_HOSTS', ''))
    ))),

    // Berapa hari sebelum lisensi resmi habis, pengingat mulai muncul di lonceng notifikasi.
    'reminder_days' => (int) env('LICENSE_REMINDER_DAYS', 3),
];
