# SI-KANCIL — Implementation Plan
## Sistem Informasi Kendali Kelengkapan SPJ Digital

### Deskripsi Singkat
Aplikasi web Laravel untuk membantu Sekretaris Tim mengontrol proses administrasi SPJ. Bukan pengganti BOS/SAKTI/PortalGO — hanya alat pencatat, monitor, dan kontrol internal.

---

## Keputusan Teknis Final

| Aspek | Pilihan |
|---|---|
| **Framework** | Laravel 12 |
| **Database** | MySQL (si_kancil, host 127.0.0.1, port 3306) |
| **CSS Framework** | Tailwind CSS |
| **Auth** | Laravel Breeze (blade stack, tanpa fitur Register) |
| **Kanban drag-drop** | SortableJS (ringan, tanpa Vue/React) |
| **Kalender** | FullCalendar 6 |
| **Date range picker** | Litepicker (ringan, vanilla JS) |
| **File upload** | Laravel Storage (local disk) |
| **Template engine** | Blade |
| **User Default** | Username: `sekprod`, Password: `Sekprod7504!` |

> [!IMPORTANT]
> Semua UI dibangun dengan **Blade + Tailwind CSS** (tanpa Vue/React) agar tetap sederhana dan mudah dipahami. Kanban FPA bersifat interaktif (drag-and-drop mengubah status SPJ).

---

## Proposed Changes

### Sprint 1 — Setup, Auth & Migrasi Database
- Konfigurasi `.env` ke MySQL (si_kancil)
- Install Laravel Breeze (blade) dan hapus halaman register
- Buat seluruh migration table:
  `expense_types`, `requests`, `document_templates`, `spj_checklists`, `checklist_histories`, `request_status_histories`, `surat_tugas_details`, `travel_details`, `real_expense_details`, `travel_reports`, `templates`
- Buat model untuk seluruh table
- Buat seeder: `ExpenseTypeSeeder` (6 jenis), `DocumentTemplateSeeder`, `UserSeeder`
- Jalankan `php artisan migrate:fresh --seed`

### Sprint 2 — Master Jenis Pengeluaran & Modul Permintaan/FPA
#### Models & Controllers
- `ExpenseType`, `Request`
- `RequestController` (CRUD)

#### Views (di `resources/views/requests/`)
- `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`

#### Fitur
- CRUD permintaan
- Search FPA
- Field `status_spj` enum: `Persiapan`, `Dikirim ke PPK`, `Perbaikan`, `Selesai`

### Sprint 3 — Checklist SPJ & Template Checklist
#### Models & Controllers
- `DocumentTemplate`, `SpjChecklist`
- `SpjChecklistController`

#### Logika otomatis
- Saat FPA dibuat → loop `document_templates` sesuai `expense_type_id` → insert ke `spj_checklists` dengan status awal `Belum Ada`
- Status Checklist: `Belum Ada` | `Belum Lengkap` | `Lengkap` | `Perlu Perbaikan`

### Sprint 4 — Kanban Checklist Dokumen & History
#### Controllers & Models
- `ChecklistKanbanController` (Endpoint AJAX: `PATCH /checklists/{id}/status`)
- `ChecklistHistory`

#### Fitur
- SortableJS untuk Kanban 4 kolom di halaman detail FPA
- Update status DB via AJAX dan tampilkan history di sidebar

### Sprint 5 — Workflow Status SPJ & History Status
#### Controllers & Models
- `RequestStatusController` (`POST /requests/{id}/status`)
- `RequestStatusHistory`

#### Aturan status
- Kanban FPA interaktif (drag-and-drop mengubah status)
- Tombol ubah status + timeline history di detail FPA
- Logika input mandatory berdasarkan status yang dituju (misal tanggal selesai, file bukti, dll)

### Sprint 6 — Detail Dokumen & Upload File
#### Controllers & Models
- Detail Surat Tugas, SPD, Pengeluaran Riil, Laporan Perjalanan
- `FileUploadController` untuk storage lokal (`storage/app/spj-files/`) dengan Max size 10MB (PDF, JPG, PNG, DOCX)

### Sprint 7 — Dashboard Kanban FPA, Kalender & Repository Template
- `DashboardController`: Cards statistik, tabel FPA, Kanban FPA (4 kolom interaktif)
- `CalendarController`: FullCalendar 6 dengan drag-select range tanggal
- `TemplateController`: CRUD template dokumen (KAK, Surat Tugas, Laporan Perjalanan, Visum, Superkendis, Dokumen SPJ)

### Sprint 8 — Layout, UI Polish & Testing
- Layout app (sidebar) dan auth
- Komponen blade (badge, card, modal)
- Seed data dummy
- Final testing

### Sprint 9 — Finalisasi Status SPJ, FPA, Surat Tugas Multi-Pelaksana & Superkendis
- Finalisasi status SPJ menjadi 4 status + validasi transisi workflow (Perbaikan opsional)
- Dukungan FPA tanpa nomor (nullable `nomor_fpa`); nomor diwajibkan saat "Dikirim ke PPK"; validasi checklist `is_required` Lengkap; cek nomor FPA live via AJAX
- Kalender: pilih tanggal → form FPA dengan deadline otomatis (end + 3 hari, editable)
- FPA form: periode via toggle (Bulanan/Triwulanan/Subround/Semester/Tahunan)
- Surat Tugas multi-pelaksana dengan nomor sub otomatis (`B-1027.1/...`, `.2/...`) via tabel `surat_tugas_pelaksanas`
- Superkendis: generate DOCX/PDF di detail FPA (hanya jika checklist Surat Tugas Lengkap), NIP opsional → "-", bulk Pisah file (ZIP) & Gabung satu file
- Tabel `sk_rate_perjalanan` (kecamatan + besaran biaya transport)
- Library baru: `phpoffice/phpword`, `dompdf/dompdf`

### Sprint 10 — Master SK Rate Management & Peningkatan Generate Superkendis
- Halaman management SK Rate Perjalanan (index dengan pencarian/filter, create, edit, destroy)
- Tabel & model baru `sk_rate_perjalanan_histories` untuk riwayat perubahan SK Rate (data sebelum/sesudah, aksi, user, waktu); riwayat tetap tersimpan walau rate dihapus (FK `nullOnDelete`)
- Rework halaman generate Superkendis: pilihan pelaksana via checkbox, setiap pelaksana memiliki input sendiri (kecamatan tujuan, tanggal perjalanan, NIP)
- Export Superkendis dari pilihan pelaksana: format DOCX/PDF, metode Pisah ZIP / Gabung satu file; validasi setiap pelaksana terpilih wajib isi kecamatan & tanggal
- Generate dokumen berbasis template (`TemplateProcessor`) menggunakan `storage/app/public/[template] Superkendis.docx` dengan placeholder `{{...}}` (bukan hardcode)
- Placeholder didukung: nama, NIP, nomor & tanggal surat tugas, tanggal perjalanan, biaya transport (dari SK Rate), terbilang, jenis perjalanan, jabatan
- Helper baru `App\Support\Terbilang` untuk konversi angka → kata (berbasis data SK Rate)
- Integrasi SK Rate: besaran biaya transport & terbilang diambil dari kecamatan tujuan sesuai SK Rate

### Document Generation Improvement
- Memperbaiki generate DOCX berbasis template agar hasil final mempertahankan layout Word asli
- Flow baru: `Template DOCX → TemplateProcessor → DOCX final` (tanpa `IOFactory::load()` + salin elemen ke PhpWord baru yang merusak struktur Word)
- Mempertahankan tabel (mis. Daftar Pengeluaran Riil), border, alignment, dan tanda tangan sesuai template
- Export PDF: load hasil DOCX (struktur utuh) lalu konversi langsung ke PDF
- Gabung (merged): isi template per pelaksana lalu gabungkan isi `<w:body>` per dokumen agar tabel/border/style tetap utuh

### Document Generation Improvement v2
- Mengganti template Superkendis menjadi `storage/app/public/[template] Superkendis 2.docx` (`TEMPLATE_PATH = '[template] Superkendis 2.docx'`)
- Standardisasi placeholder tetap memakai `{{ }}` (`setMacroChars('{{','}}')`), bukan `${}`
- Placeholder v2: `{{nama}}`, `{{NIP}}`, `{{nomor surat tugas}}`, `{{tanggal surat tugas}}`, `{{tanggal perjalanan}}`, `{{biaya sk}}`, `{{terbilangnya berapa}}`, `{{jenis kegiatan}}`, `{{jabatan}}`
- Pemetaan jenis kegiatan → jabatan: Pelatihan → PCL, Pendataan Lapangan → PCL, Pengawasan Lapangan → PML, Supervisi Lapangan → Supervisor
- Semua tanggal generate dalam format Indonesia (mis. "25 Juli 2026"), bukan `Y-m-d` / `d-m-Y`
- `cleanupTemplate` digeneralisasi: setelah penggabungan run, seluruh placeholder `{{key}}` diganti dari data agar tidak ada yang tertinggal (menangani `{{jenis kegiatan}}` yang terpecah antar-run)
- Mempertahankan perbaikan layout: tabel Daftar Pengeluaran Riil tetap muncul, border sesuai template, posisi tanda tangan tetap, nama tidak menempel teks sebelumnya

### Surat Tugas Completion Validation & Superkendis Flow
- Validasi status "Lengkap" Surat Tugas secara terpusat lewat service `App\Services\SuratTugasService` (garis syarat: Nomor Surat Tugas, Tanggal Surat Tugas, Isi Tugas, minimal 1 Pelaksana) agar tidak ada duplikasi logic
- Dropdown status (`SpjChecklistController@update`) dan kanban drag-and-drop (`ChecklistKanbanController@updateStatus`) memakai validasi yang sama
- Dropdown: bila meminta "Lengkap" namun data belum lengkap → perubahan dibatalkan, kembali dengan pesan kekurangan data (contoh: "Surat Tugas belum lengkap. Lengkapi Nomor Surat Tugas, Tanggal Surat Tugas, Isi Tugas, minimal 1 Pelaksana.")
- Kanban: bila dipindah ke "Lengkap" dan gagal validasi → respons `success=false` (HTTP 422), card dikembalikan ke kolom semula, notifikasi ditampilkan
- Tombol "Generate Superkendis" di halaman Detail FPA hanya muncul jika Status Surat Tugas = Lengkap (dan memiliki pelaksana)
- Input pelaksana massal pada form Surat Tugas: textarea + pilihan pemisah (Baris baru / Titik koma `;` / Koma `,`) + pratinjau bernomor + konfirmasi sebelum ditambahkan ke daftar; tetap mempertahankan "Tambah Pelaksana satu per satu"
- Nomor surat sub otomatis dipertahankan (contoh: `B-1041/75040/KP.650/2026` → `B-1041.1/...`, `B-1041.2/...`, `B-1041.3/...`)
- Generate Superkendis mengambil data dari Daftar Pelaksana Surat Tugas (nama, nomor surat sub); tidak membuat form detail baru "Pengeluaran Riil & Surat Non Kendaraan Dinas"

---

## Struktur Database Final
```
expense_types          (id, nama, kode, keterangan, is_active)
document_templates     (id, expense_type_id, nama_dokumen, is_required, urutan)
requests               (id, nomor_fpa [nullable], deskripsi_permintaan, jenis_pengeluaran_id, periode, tanggal_mulai, tanggal_selesai, lokasi [hapus], deadline_spj, status_spj [4 status], user_id, tanggal_kirim_ppk, tanggal_selesai_spj)
spj_checklists         (id, request_id, nama_dokumen, status, catatan, file_path, urutan, is_required)
checklist_histories    (id, checklist_id, status_lama, status_baru, catatan, user_id)
request_status_histories (id, request_id, status_lama, status_baru, catatan, file_bukti, user_id)
surat_tugas_details    (id, checklist_id, nomor_surat_tugas, tanggal_surat_tugas, pelaksana [nullable/legacy], isi_tugas)
surat_tugas_pelaksanas (id, surat_tugas_detail_id, nama_pelaksana, nomor_surat, urutan)
sk_rate_perjalanan     (id, kecamatan, ibukota_kecamatan, besaran_biaya_transport, keterangan)
sk_rate_perjalanan_histories (id, sk_rate_perjalanan_id [nullable, nullOnDelete], data_sebelum, data_sesudah, aksi, user_id)
travel_details         (id, checklist_id, nomor_spd, nama_pelaksana, maksud_perjalanan, tempat_berangkat, tempat_tujuan, tanggal_berangkat, tanggal_kembali, transportasi)
real_expense_details   (id, checklist_id, nomor_surat_tugas, tanggal_surat_tugas, nama_pelaksana, jabatan, tanggal_kegiatan, uraian_pengeluaran, jumlah_pengeluaran, keterangan)
travel_reports         (id, checklist_id, nama_pelaksana, tujuan, uraian_kegiatan, tanggal_kegiatan, dokumentasi)
templates              (id, nama_template, kategori, versi, file, status_aktif)
users                  (default Laravel users table)
```

