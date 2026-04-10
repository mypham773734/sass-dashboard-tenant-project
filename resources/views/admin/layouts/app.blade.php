<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FlowSaaS | Project Dashboard</title>
    <!-- Tailwind CSS v3 + Font Awesome + Google Fonts (Inter) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @stack('styles')
</head>

<body class="font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- ======================= SIDEBAR ======================= -->
        @include('admin.partials.sidebar')

        <!-- ======================= MAIN CONTENT (RIGHT SIDE) ======================= -->
        <div class="flex-1 ml-72 flex flex-col h-screen overflow-hidden">

            <!-- ======================= HEADER ======================= -->
            @include('admin.partials.header')

            <!-- ======================= MAIN SCROLLABLE CONTENT ======================= -->
            @yield('content')

            <!-- ======================= FOOTER ======================= -->
            @include('admin.partials.footer')
        </div>
    </div>

    @vite([
        'resources/js/app.js',
        'resources/sass/app.scss'
    ])

    @vite('resources/js/bases/index.js')

    @stack('script')
    @stack('scripts')
</body>

</html>