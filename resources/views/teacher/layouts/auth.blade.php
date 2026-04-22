<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'قسم الأستاذ') | بعيد ليرن</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="teacher-auth" data-theme-scope="teacher">
    @yield('content')
</body>
</html>
