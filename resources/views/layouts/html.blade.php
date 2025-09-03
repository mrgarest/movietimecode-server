<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    {!! seo($SEOData ?? null) !!}
    @yield('meta')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/flaticon-uicons-main/src/uicons/css/all/all.css', 'resources/js/app.js'])
    @yield('head')
    @yield('head.script')
</head>

<body>
    @yield('body')
    @isset($jsonPageData)
        <script id="JSON_PAGE_DATA" type="application/json">{!!json_encode($jsonPageData)!!}</script>
        @vite('resources/js/json-page-data.js')
    @endisset
    @yield('body.end')
</body>

</html>
