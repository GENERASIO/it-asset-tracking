<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Scan Aset' }}</title>
    @vite(['resources/css/app.css', 'resources/js/scanner.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    {{ $slot }}
</body>
</html>