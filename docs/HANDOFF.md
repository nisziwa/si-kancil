# SI-KANCIL Agent Handoff

## Current Sprint
Sprint aktif: Sprint 10 — Master SK Rate Management & Peningkatan Generate Superkendis

## Current Status
Seluruh tahapan pengembangan (Sprint 1 s/d Sprint 10) pada aplikasi SI-KANCIL telah selesai 100% dan seluruh 55 automated unit & feature tests berhasil tanpa error.

## Completed
Daftar pekerjaan yang sudah selesai:
- Sprint 1 Setup, Auth & Database Migration
- Sprint 2 Master Jenis Pengeluaran & Modul Permintaan/FPA
- Sprint 3 Checklist SPJ & Template Checklist
- Sprint 4 Kanban Checklist & History
- Sprint 5 Workflow Status SPJ & History
- Sprint 6 Detail Dokumen & Upload File
- Sprint 7 Dashboard, Kanban FPA, Kalender & Template
- Sprint 8 Layout, UI Polish & Testing
- Sprint 9 Finalisasi Status SPJ (4 status + validasi transisi), FPA tanpa nomor, Surat Tugas multi-pelaksana dengan nomor sub otomatis, dan Superkendis (DOCX/PDF, ZIP, gabung)
- Sprint 10 Master SK Rate Management (CRUD + search + history) dan peningkatan Generate Superkendis (pilihan pelaksana via checkbox + input per pelaksana, generate berbasis template Superkendis.docx, export DOCX/PDF, Pisah ZIP / Gabung)

## Remaining Tasks
Daftar pekerjaan yang belum selesai:
- Tidak ada (Semua fitur telah selesai diimplementasikan)

## Important Decisions
Keputusan penting:
- Framework: Laravel 12
- Database: MySQL (si_kancil)
- Authentication: Laravel Breeze (Blade Stack) tanpa fitur register. Default user: `sekprod`, pass: `Sekprod7504!`
- Library: Tailwind CSS, SortableJS, FullCalendar 6, Litepicker, phpoffice/phpword, dompdf/dompdf
- Business rules: Kanban FPA interaktif drag and drop (mengubah status FPA dan mencatat riwayat)
- Status SPJ final (4): `Persiapan`, `Dikirim ke PPK`, `Perbaikan`, `Selesai` — transisi divalidasi; `nomor_fpa` nullable tetapi wajib saat "Dikirim ke PPK"
- Superkendis digenerate dari pelaksana Surat Tugas (tabel `surat_tugas_pelaksanas`) via `SuperkendisController`; generate berbasis template `[template] Superkendis.docx` menggunakan `TemplateProcessor` dengan `setMacroChars('{{','}}')`
- SK Rate Perjalanan dikelola via halaman management; riwayat perubahan dicatat di `sk_rate_perjalanan_histories` (FK `nullOnDelete` agar riwayat tetap tersimpan walau rate dihapus)
- Besaran biaya transport & terbilang pada Superkendis diambil dari SK Rate berdasarkan kecamatan tujuan menggunakan helper `App\Support\Terbilang`

## Last Commit
Commit terakhir: (menunggu final commit Sprint 10)
