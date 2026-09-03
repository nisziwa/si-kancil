# SI-KANCIL Agent Handoff
## Current Sprint
Sprint aktif: Superkendis Flow Improvement & FPA Kanban Enhancement

## Current Status
Seluruh tahapan pengembangan (Sprint 1 s/d Sprint 13) pada aplikasi SI-KANCIL telah selesai 100% dan seluruh 75 automated unit & feature tests berhasil tanpa error.
Superkendis kini dipersistkan per-pelaksana ke tabel `superkendis` (file DOCX/PDF tersimpan), checklist "Pengeluaran Riil + Surat Non Kendaraan Dinas" otomatis jadi Lengkap saat semua pelaksana tergenerate, validasi status FPA dipusatkan ke `RequestStatusService`, tersedia bulk-move Kanban FPA dengan hasil per-FPA, dan peringatan drag Kanban tidak lagi reload halaman.
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
- Sprint 11 (Document Generation Improvement): memperbaiki generate DOCX berbasis template agar mempertahankan layout Word (tabel, border, alignment, tanda tangan); flow `Template DOCX → TemplateProcessor → DOCX final` tanpa rebuild elemen
- Sprint 11b (Document Generation Improvement v2): penggunaan template `[template] Superkendis 2.docx`, standardisasi placeholder `{{ }}`, pemetaan jenis kegiatan → jabatan (Pelatihan/Pendataan → PCL, Pengawasan → PML, Supervisi → Supervisor), format tanggal Indonesia, dan perbaikan layout generate dokumen
- Sprint 12 (Surat Tugas Completion Validation & Superkendis Flow): validasi status Lengkap terpusat (service) untuk dropdown & kanban, tombol Generate Superkendis hanya saat Surat Tugas lengkap, input pelaksana massal dengan pratinjau & konfirmasi, nomor surat sub otomatis
- Sprint 13 (Superkendis Flow Improvement & FPA Kanban Enhancement): tabel `superkendis` + persistensi file dan record per-pelaksana, checklist "Pengeluaran Riil + Surat Non Kendaraan Dinas" otomatis Lengkap, perbaikan bug `?pelaksana=`, refactor validasi status FPA ke `RequestStatusService`, bulk-move Kanban FPA per-FPA, dan peringatan drag Kanban tanpa reload (modal ≥8 detik)

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
- Superkendis digenerate dari pelaksana Surat Tugas (tabel `surat_tugas_pelaksanas`) via `SuperkendisController`; generate berbasis template `[template] Superkendis 2.docx` menggunakan `TemplateProcessor` dengan `setMacroChars('{{','}}')`
- Placeholder Superkendis v2: `{{nama}}`, `{{NIP}}`, `{{nomor surat tugas}}`, `{{tanggal surat tugas}}`, `{{tanggal perjalanan}}`, `{{biaya sk}}`, `{{terbilangnya berapa}}`, `{{jenis kegiatan}}`, `{{jabatan}}`
- Jenis kegiatan → jabatan: Pelatihan → PCL, Pendataan Lapangan → PCL, Pengawasan Lapangan → PML, Supervisi Lapangan → Supervisor; semua tanggal generate dalam format Indonesia (mis. "25 Juli 2026")
- Validasi kelengkapan Surat Tugas terpusat di `App\Services\SuratTugasService` (Nomor, Tanggal, Isi Tugas, minimal 1 Pelaksana); dipakai dropdown (`SpjChecklistController`) dan kanban (`ChecklistKanbanController`); kanban mengembalikan card ke kolom semula saat validasi gagal
- SK Rate Perjalanan dikelola via halaman management; riwayat perubahan dicatat di `sk_rate_perjalanan_histories` (FK `nullOnDelete` agar riwayat tetap tersimpan walau rate dihapus)
- Besaran biaya transport & terbilang pada Superkendis diambil dari SK Rate berdasarkan kecamatan tujuan menggunakan helper `App\Support\Terbilang`
- Document Generation: hasil DOCX dihasilkan langsung dari `TemplateProcessor` (setelah `saveAs`) tanpa `IOFactory::load()` + salin elemen ke PhpWord baru, karena hal itu merusak struktur Word (tabel/border/alignment/tanda tangan); PDF dikonversi dari DOCX berstruktur utuh; Gabung (merged) menggabungkan isi `<w:body>` per pelaksana agar layout tetap utuh; `cleanupTemplate` digeneralisasi untuk mengganti seluruh `{{key}}` yang terpecah antar-run

## Last Commit
Commit terakhir: `feat: improve superkendis flow and fpa kanban validation`
