# SI-KANCIL Progress

## Sprint 1 — Setup, Auth & Migrasi Database
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: ceb9138
Ringkasan Perubahan:
- Setup Laravel Breeze (blade) dan hapus halaman register.
- Konfigurasi `.env` dan pembuatan database MySQL (`si_kancil`).
- Pembuatan struktur migration untuk 11 tabel.
- Pembuatan Model dan relationships untuk semua entitas.
- Pembuatan Seeder (User default: sekprod, Expense Type, Document Template).
- Eksekusi `php artisan migrate:fresh --seed` berhasil.

## Sprint 2 — Master Jenis Pengeluaran & Modul Permintaan/FPA
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: 45cdcde
Ringkasan Perubahan:
- Pembuatan RequestController untuk FPA
- Pembuatan Views untuk requests (index, create, edit, show)
- Konfigurasi validasi pada requests form
- Integrasi Jenis Pengeluaran pada form FPA
- Pencarian dan filter status FPA di halaman index
- Penambahan link navigasi FPA di layout utama
- Mendaftarkan resource route requests di web.php

## Sprint 3 — Checklist SPJ & Template Checklist
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: cdc307a
Ringkasan Perubahan:
- Logic auto-generate Checklist SPJ dari DocumentTemplate saat pembuatan FPA
- Tampilan list checklist pada halaman Detail FPA
- Pembuatan SpjChecklistController
- Pembuatan view edit untuk mengelola checklist secara individual
- Menambahkan route dan aksi terkait checklist di detail FPA

## Sprint 4 — Kanban Checklist & History
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: 0f5f107
Ringkasan Perubahan:
- Pembuatan ChecklistKanbanController untuk menerima request AJAX PATCH perubahan status checklist
- Integrasi SortableJS untuk Kanban 4 kolom di halaman detail FPA
- Pencatatan history ke dalam tabel checklist_histories setiap kali card dipindahkan
- Penambahan area History Sidebar pada detail FPA untuk menampilkan riwayat perubahan

## Sprint 5 — Workflow Status SPJ & History
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: 05f4d54
Ringkasan Perubahan:
- Pembuatan RequestStatusController untuk workflow perubahan status SPJ (form & AJAX)
- Validasi input mandatory sesuai status tujuan (tanggal_kirim_ppk, tanggal_selesai_spj, catatan, file bukti)
- Pembuatan partial status-workflow dengan visual step indicator dan form dinamis
- Menampilkan timeline riwayat perubahan status SPJ beserta file bukti
- Menghubungkan storage symlink untuk public upload dokumen bukti
- Automated feature testing (RequestStatusTest) untuk semua skenario validasi dan riwayat status

## Sprint 6 — Detail Dokumen & Upload File
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: 813cffb
Ringkasan Perubahan:
- Implementasi penyimpanan detail dokumen terstruktur: Surat Tugas, SPD/SPPD, Pengeluaran Riil & Non-Kendaraan Dinas, Laporan Perjalanan
- Implementasi FileUploadController dan upload file (Max 10MB; PDF, JPG, PNG, DOCX) untuk dokumen checklist SPJ
- Pembaruan view edit checklist (`checklists/edit.blade.php`) dengan form kondisional dan download link file tersimpan
- Penambahan badge file dan direct edit link pada kanban card
- Automated feature testing (DocumentDetailTest) untuk verifikasi upload & detail dokumen terstruktur

## Sprint 7 — Dashboard, Kanban FPA, Kalender & Template
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: 8864213
Ringkasan Perubahan:
- Pembuatan DashboardController dengan 6 cards statistik, filter bulan/tahun/keyword, dan tabel ringkasan FPA
- Pembuatan 6-Kolom Kanban FPA interaktif dengan integrasi SortableJS dan live AJAX status change (`PATCH /requests/{id}/status-ajax`)
- Pembuatan CalendarController dan integrasi FullCalendar 6 dengan endpoint JSON events (`GET /calendar/events`), modal detail event, dan drag-select tanggal
- Pembuatan TemplateController untuk CRUD Repository Template Dokumen (upload file max 20MB, filter kategori, download, status aktif)
- Penambahan menu navigasi lengkap: Dashboard, Permintaan / FPA, Kalender Kegiatan, Repository Template
- Automated feature testing (Sprint7FeatureTest) untuk Dashboard, Calendar API, dan Template CRUD workflow

## Sprint 8 — Layout, UI Polish & Testing
Status: Completed
Tanggal Selesai: 2026-08-30
Commit Hash: d0d8da2
Ringkasan Perubahan:
- Desain ulang halaman landing page (`welcome.blade.php`) dengan branding SI-KANCIL dan alur bisnis interaktif
- Pembuatan DummyDataSeeder lengkap (template dokumen, multi-status FPA, checklist otomatis, detail dokumen, status history)
- Integrasi DummyDataSeeder ke DatabaseSeeder
- Automated feature test untuk verifikasi DatabaseSeeder
- Verifikasi menyeluruh: seluruh 34 unit & feature tests berhasil 100% (PASS)

## Sprint 9 — Finalisasi Status SPJ, FPA, Surat Tugas Multi-Pelaksana & Superkendis
Status: Completed
Tanggal Selesai: 2026-08-31
Ringkasan Perubahan:
- Finalisasi status SPJ menjadi 4 status (`Persiapan`, `Dikirim ke PPK`, `Perbaikan`, `Selesai`); status lama `Pelaksanaan` & `Pengumpulan SPJ` dihapus
- Validasi transisi workflow (Perbaikan opsional): Persiapan→Dikirim ke PPK→(Perbaikan)→Selesai
- "Dikirim ke PPK" wajib punya nomor FPA dan seluruh checklist `is_required=true` Lengkap; pesan blokir standar
- SPJ progress = indikator prioritas (deadline/sisa hari/keterlambatan), bukan progress checklist
- `nomor_fpa` nullable; tampilan abu-abu "Belum ada nomor FPA"; cek duplikat nomor FPA live via AJAX
- Kalender: pilih tanggal langsung membuka form FPA dengan deadline otomatis (end+3 hari, editable) tanpa alert/confirm
- FPA form: input `lokasi` & `periode` teks dihapus; periode diganti toggle (Bulanan/Triwulanan/Subround/Semester/Tahunan)
- Surat Tugas multi-pelaksana: tabel baru `surat_tugas_pelaksanas` dengan nomor sub otomatis (`B-1027.1/...`, `.2/...`)
- Superkendis (SuperkendisController): generate DOCX/PDF per pelaksana di detail FPA, NIP opsional (kosong → "-"), bulk Pisah file (ZIP) & Gabung satu file
- Tabel & seeder baru `sk_rate_perjalanan` (kecamatan, besaran biaya transport)
- Library baru: `phpoffice/phpword` & `dompdf/dompdf`
- Automated feature testing (RequestStatusTest, DocumentDetailTest, SuperkendisTest) untuk workflow status, FPA tanpa nomor, pelaksana sub-nomor, pengecekan nomor FPA live (AJAX) & Superkendis DOCX/PDF
- Verifikasi menyeluruh: seluruh 48 unit & feature tests berhasil 100% (PASS)
