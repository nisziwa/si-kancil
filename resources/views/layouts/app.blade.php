<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Flatpickr Date Picker -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Flatpickr JS -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Single date picker
                document.querySelectorAll('.datepicker').forEach(function(el) {
                    flatpickr(el, {
                        locale: 'id',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'j F Y',
                        allowInput: true,
                    });
                });

                // Range date picker
                document.querySelectorAll('.datepicker-range').forEach(function(el) {
                    var startInput = document.getElementById(el.dataset.startInput);
                    var endInput = document.getElementById(el.dataset.endInput);
                    
                    flatpickr(el, {
                        locale: 'id',
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'j F Y',
                        allowInput: false,
                        defaultDate: [startInput?.value, endInput?.value].filter(Boolean),
                        onChange: function(dates) {
                            if (dates.length >= 1) {
                                startInput.value = flatpickr.formatDate(dates[0], 'Y-m-d');
                            }
                            if (dates.length >= 2) {
                                endInput.value = flatpickr.formatDate(dates[1], 'Y-m-d');
                                endInput.dispatchEvent(new Event('change'));
                            }
                        }
                    });
                });
            });
        </script>
    </body>
</html>
