# SI-KANCIL Agent Handoff
## Current Sprint
Sprint aktif: Checklist Detail Flow Refactor & Superkendis Improvement

## Current Status
Seluruh tahapan pengembangan (Sprint 1 s/d Sprint 14) pada aplikasi SI-KANCIL telah selesai 100% dan seluruh automated unit & feature tests berhasil tanpa error. Sprint 15 (QA Bug Fix - Superkendis & Workflow Validation) menyelesaikan 8 bug hasil audit: page break antar pelaksana pada dokumen gabungan, konsistensi validasi status (dropdown/kanban/bulk), pembatasan edit FPA hanya saat Persiapan, bulk select kartu Kanban FPA, UX Laporan Perjalanan, UX Superkendis (tanpa alert, loading state), verifikasi PDF DOMPDF, dan pembersihan UI (query dipindah ke controller, flash message konsisten). Ditambah tindak lanjut atas 5 keluhan user: status "Generated" tampil langsung tanpa refresh, double download dihilangkan, page break gabungan diperbaiki permanen (paragraf `<w:br w:type="page"/>`), isian Superkendis sebelumnya tampil otomatis (baris dengan record `superkendis` tercentang + prefill tetap editable), dan border PDF tidak lagi seluruhnya tebal (`setEditCallback` menghapus CSS default `table`/`td 1px solid black` milik PhpWord, `border-collapse: collapse`, border asli template dipertahankan). Seluruh 83 unit & feature tests berhasil 100% (PASS).
Detail dokumen (SPD/SPPD, Pengeluaran Riil, Laporan Perjalanan) kini memakai daftar pelaksana yang bersumber dari Surat Tugas (tanpa input ulang manual), bug isi detail kosong diperbaiki, status pengumpulan Laporan Perjalanan disimpan per-pelaksana (bulk) ke tabel `travel_report_pelaksanas` dengan konfirmasi popup di Kanban, dan halaman Superkendis menampilkan status + download per baris dengan perilaku download langsung DOCX untuk 1 pelaksana (ZIP/gabung untuk lebih dari satu).
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
- Sprint 14 (Checklist Detail Flow Refactor & Superkendis Improvement): detail dokumen (SPD/SPPD, Pengeluaran Riil, Laporan Perjalanan) memakai daftar pelaksana bersumber dari Surat Tugas (tanpa input ulang), fix bug isi detail kosong, status pengumpulan Laporan Perjalanan per-pelaksana (bulk) ke tabel `travel_report_pelaksanas`, konfirmasi popup di Kanban, dan Superkendis dengan status + download per baris (1 pelaksana download DOCX langsung, >1 ZIP/gabung)
- Sprint 15 (QA Bug Fix - Superkendis & Workflow Validation): 8 bug audit diselesaikan - page break antar pelaksana file gabungan (appendBody menyisipkan paragraf page-break eksplisit `<w:br w:type="page"/>`, bukan sectPr mid-body), konsistensi validasi status dropdown/kanban/bulk (RequestStatusService `requiredFields()` terpusat, Selesai butuh tanggal, catatan optional, tanggal auto-fill hari ini untuk kanban/bulk), FPA hanya bisa diedit saat Persiapan (guard `RequestController@update()`), bulk select kartu Kanban FPA (checkbox kartu + bar aksi bulk via `requests.status.bulk`), UX Laporan Perjalanan, UX Superkendis (feedback in-page ganti alert + loading state + `session('error')`), verifikasi PDF DOMPDF, dan UI cleanup (query history dipindah ke controller, flash konsisten, lebar halaman `max-w-7xl`)
- Sprint 15 Follow-up Feedback (Superkendis UX): status "Generated" langsung tampil tanpa refresh (reload via `sessionStorage` setelah unduh blob); double download dihilangkan (`e.preventDefault()` di awal handler submit); page break gabungan permanen (paragraf `<w:br w:type="page"/>` di `appendBody`); isian sebelumnya otomatis tampil saat membuka halaman Superkendis (baris dengan record `superkendis` tercentang + prefill data terakhir, tetap editable, regenerate memakai update/create per `surat_tugas_pelaksana_id` tanpa duplikat); border PDF tidak lagi seluruhnya tebal (`SuperkendisController@pdfWriter` + `setEditCallback` → `stripDefaultTableBorders()` menghapus CSS default `table`/`td` 1px solid black, aktifkan `border-collapse: collapse`, dan pertahankan border asli template). Seluruh 83 test PASS.

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
- QA Bug Fix: validasi lapangan status SPJ terpusat di `RequestStatusService@requiredFields()` dan dipakai seluruh jalur (dropdown `update`, kanban `updateAjax`, bulk `bulk`); Selesai wajib `tanggal_selesai_spj` (dropdown eksplisit, kanban/bulk auto-fill hari ini via `_auto_field_`); `catatan` optional di semua jalur; FPA hanya dapat diedit/dihapus saat status Persiapan; page break gabungan Superkendis memakai paragraf page-break eksplisit `<w:p><w:r><w:br w:type="page"/></w:r></w:p>` di antara blok pelaksana (bukan `<w:sectPr>` yang tidak valid di tengah `<w:body>`)
- PDF Superkendis: hasil konversi PhpWord→DOMPDF memakai writer dengan `setEditCallback` (`SuperkendisController@pdfWriter`) yang menghapus CSS global bawaan `table/td {border: 1px solid black}` agar border PDF mengikuti template DOCX (hanya tabel yang memang bergaris), mengaktifkan `border-collapse: collapse`, serta mempertahankan border asli sel (0.2pt)

## Sprint 16 - Superkendis UX & Format Tanggal Global
- Kebijakan tanggal: input `<input type="date">` tetap (browser kirim `Y-m-d`, DB tidak diubah); hanya teks tampilan diubah ke `dd-mm-yyyy` (timestamp `d/m/Y H:i` → `d-m-Y H:i`). File: `requests/show.blade.php`, `dashboard.blade.php`, `partials/status-workflow.blade.php`, `sk_rates/edit.blade.php`, `ChecklistKanbanController.php`, `CalendarController.php`.
- UX input NIP di `requests/superkendis.blade.php`: scroll horizontal mengikuti cursor saat mengetik, reset ke awal saat blur; value tidak diubah. Tabel Superkendis `table-layout: fixed` + `<colgroup>` min-width (50/220/260/220/160/200/180/180/130/120) + scroll horizontal; dropdown tampil label lengkap.
- Automated testing: `SuperkendisTest@test_superkendis_index_page_renders` diperkuat. Seluruh 83 test PASS (269 assertions). Kode diformat ulang dengan Laravel Pint.

## Indonesian Date Display Standard
- Standar tampilan tanggal Indonesia `dd MMMM yyyy` (mis. `02 September 2026`); database tetap `Y-m-d`, tipe kolom `date` tidak diubah, input `<input type="date">` tetap mengirim `Y-m-d` — hanya teks tampilan yang diformat agar tidak ambigu.
- Formatter terpusat `App\Support\Tanggal` (`format` → `dd MMMM yyyy`, `formatDateTime` → `dd MMMM yyyy HH:mm`), menerima Carbon/DateTime/string, fallback saat kosong; pola sama dengan `App\Support\Terbilang`, dipakai di semua Blade (bukan formatter per-Blade).
- Area diterapkan: `requests/show.blade.php`, `dashboard.blade.php`, `partials/status-workflow.blade.php`, `partials/kanban-checklist.blade.php` + `ChecklistKanbanController`, `sk_rates/edit.blade.php`, `CalendarController`, `SuperkendisController` (tanggal surat tugas & perjalanan DOCX/PDF). Label/helper input diperbarui ke format Indonesia.
- Test baru `TanggalTest`. Komit: `feat: standardize Indonesian date display format` (menutup GitHub Issue #10).

## Last Commit
Commit terakhir: `fix: improve superkendis ux and date formatting` (menutup GitHub Issue #9).
