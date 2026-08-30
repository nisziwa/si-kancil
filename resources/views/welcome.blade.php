<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SI-KANCIL — Kendali Kelengkapan SPJ Digital</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 selection:bg-blue-600 selection:text-white">
    <div class="min-h-screen flex flex-col justify-between">
        
        <!-- Header / Navbar -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-xl shadow">
                        🦌
                    </div>
                    <div>
                        <span class="font-black text-lg text-blue-900 tracking-tight">SI-KANCIL</span>
                        <span class="hidden sm:inline text-xs text-gray-500 ml-2 font-medium">Sistem Kendali SPJ Digital</span>
                    </div>
                </div>
                <div>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition shadow">
                                Buka Dashboard →
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition shadow">
                                Masuk Sistem
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16 flex flex-col justify-center">
            <div class="text-center max-w-3xl mx-auto space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold uppercase tracking-wider">
                    ✨ Kontrol Administrasi & Pertanggungjawaban SPJ
                </div>
                
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    Sistem Informasi Kendali Kelengkapan <span class="text-blue-600">SPJ Digital</span>
                </h1>
                
                <p class="text-base sm:text-lg text-gray-600 leading-relaxed">
                    Aplikasi pengendali kelengkapan berkas administrasi dan monitoring proses SPJ Tim Produksi. Mempermudah pemantauan batas waktu, kelengkapan berkas, alur perbaikan, dan ketersediaan template dokumen.
                </p>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3.5 rounded-xl text-base shadow-md transition transform hover:-translate-y-0.5">
                            Menuju Dashboard Monitoring →
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3.5 rounded-xl text-base shadow-md transition transform hover:-translate-y-0.5">
                            Masuk Akun Sekretaris
                        </a>
                    @endauth
                    <a href="#fitur" class="w-full sm:w-auto bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-semibold px-6 py-3.5 rounded-xl text-base transition">
                        Pelajari Alur Sistem
                    </a>
                </div>
            </div>

            <!-- Business Flow Cards -->
            <div id="fitur" class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Pencatatan Permintaan (FPA)</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Mencatat permintaan anggaran/kegiatan BOS secara terstruktur lengkap dengan tanggal kegiatan, lokasi, dan deadline SPJ.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xl">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Checklist Otomatis & Detail Form</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Checklist SPJ langsung ter-generate sesuai jenis pengeluaran dengan dukungan isian khusus (Surat Tugas, SPD, Pengeluaran Riil, Laporan Perjalanan) & upload berkas.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center font-bold text-xl">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Kanban & Timeline Monitoring</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Pantau posisi SPJ (Persiapan, Pelaksanaan, Pengumpulan SPJ, Dikirim ke PPK, Perbaikan, Selesai) secara visual dan interaktif.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-500 space-y-1">
                <p class="font-semibold text-gray-700">SI-KANCIL &copy; {{ date('Y') }} — Sistem Informasi Kendali Kelengkapan SPJ Digital</p>
                <p>Dikembangkan untuk mendukung efisiensi administrasi Sekretaris Tim.</p>
            </div>
        </footer>

    </div>
</body>
</html>
