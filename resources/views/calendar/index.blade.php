<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Kalender Jadwal Kegiatan SPJ') }}
            </h2>
            <a href="{{ route('requests.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                + Buat FPA Baru
            </a>
        </div>
    </x-slot>

    <!-- FullCalendar 6 CSS & JS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Legend Status Color -->
            <div class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap items-center gap-4 text-xs font-semibold">
                <span class="text-gray-500 font-bold uppercase">Petunjuk Warna:</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-500"></span> Persiapan</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span> Pelaksanaan</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-600"></span> Pengumpulan SPJ</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-600"></span> Dikirim ke PPK</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-600"></span> Perbaikan</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-600"></span> Selesai</span>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div id="calendar" class="min-h-[650px]"></div>
            </div>

        </div>
    </div>

    <!-- Modal Detail Event Kalender -->
    <div id="eventModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl space-y-4">
            <div class="flex justify-between items-start border-b pb-2">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-800"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <div class="space-y-2 text-sm text-gray-700">
                <p><strong>Nomor FPA:</strong> <span id="modalFpa"></span></p>
                <p><strong>Jenis Pengeluaran:</strong> <span id="modalJenis"></span></p>
                <p><strong>Status SPJ:</strong> <span id="modalStatus" class="font-semibold"></span></p>
                <p><strong>Lokasi:</strong> <span id="modalLokasi"></span></p>
                <p><strong>Deadline SPJ:</strong> <span id="modalDeadline" class="text-red-600 font-semibold"></span></p>
                <p><strong>Jadwal Kegiatan:</strong> <span id="modalTanggal"></span></p>
            </div>
            <div class="flex justify-end gap-2 border-t pt-3">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded text-sm font-semibold">Tutup</button>
                <a id="modalDetailLink" href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold">Buka Detail FPA</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                locale: 'id',
                selectable: true,
                selectMirror: true,
                events: '{{ route("calendar.events") }}',

                // Drag-Select Range Tanggal untuk membuat FPA baru
                select: function(info) {
                    var startDate = info.startStr;
                    // FullCalendar end date is exclusive, subtract 1 day for inclusive end date
                    var end = new Date(info.endStr);
                    end.setDate(end.getDate() - 1);
                    var endDate = end.toISOString().split('T')[0];

                    if (confirm('Buat FPA baru untuk rentang tanggal ' + startDate + ' s/d ' + endDate + '?')) {
                        window.location.href = '{{ route("requests.create") }}?tanggal_mulai=' + startDate + '&tanggal_selesai=' + endDate;
                    }
                    calendar.unselect();
                },

                // Klik Event untuk menampilkan Modal Detail
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    var props = info.event.extendedProps;

                    document.getElementById('modalTitle').innerText = info.event.title;
                    document.getElementById('modalFpa').innerText = props.nomor_fpa || '-';
                    document.getElementById('modalJenis').innerText = props.jenis_pengeluaran || '-';
                    document.getElementById('modalStatus').innerText = props.status_spj || '-';
                    document.getElementById('modalLokasi').innerText = props.lokasi || '-';
                    document.getElementById('modalDeadline').innerText = props.deadline || '-';
                    document.getElementById('modalTanggal').innerText = info.event.startStr + (info.event.endStr ? ' s/d ' + info.event.endStr : '');
                    document.getElementById('modalDetailLink').href = info.event.url;

                    document.getElementById('eventModal').classList.remove('hidden');
                }
            });
            calendar.render();
        });

        function closeModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }
    </script>
</x-app-layout>

