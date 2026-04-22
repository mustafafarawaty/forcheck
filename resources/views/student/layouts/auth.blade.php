<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'قسم الطالب') | بعيد ليرن</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="student-auth" data-theme-scope="student">
    @yield('content')
</body>
</html>
