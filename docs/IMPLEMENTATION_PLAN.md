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

### Superkendis Flow Improvement & FPA Kanban Enhancement
- Tabel baru `superkendis` (satu record per `surat_tugas_pelaksana_id`) untuk mempersistkan Superkendis; field: `id, surat_tugas_pelaksana_id, nip, kecamatan, tanggal_perjalanan, jenis_kegiatan, jabatan, file_docx, file_pdf, created_at, updated_at` (TANPA `checklist_id`, TANPA snapshot `nomor_surat`, TANPA `generated_at`)
- Tidak memakai `spj_checklists.file_path` untuk penyimpanan Superkendis; file DOCX/PDF disimpan di `storage/app/public/spj-files/superkendis/...`
- `generate()` & `bulk()` per-pelaksana: simpan file + `update`/`create` record `superkendis` berdasarkan `surat_tugas_pelaksana_id` (regenerate = perbarui, tanpa duplikat); NIP kosong tersimpan `'-'`
- Checklist "Pengeluaran Riil + Surat Non Kendaraan Dinas" otomatis jadi "Lengkap" HANYA apabila SEMUA pelaksana sudah tersimpan Superkendis-nya dan target tidak berstatus "Perlu Perbaikan"/"Lengkap"; mencatat `ChecklistHistory`; Surat Tugas & checklist lain tak pernah diubah
- Jenis kegiatan = daftar statis (tanpa master DB): `Pelatihan→PCL, Pendataan Lapangan→PCL, Pengawasan Lapangan→PML, Supervisi Lapangan→Supervisor`; jabatan tidak diinput manual
- Form Superkendis tetap tanpa form baru; nama pelaksana + nomor surat sub readonly (dari `surat_tugas_pelaksanas`), NIP/kecamatan/tanggal perjalanan/jenis kegiatan input; prefill dari record `superkendis` saat regenerate; tautan dokumen tersimpan ditampilkan
- File gabungan (merged) tetap output tambahan; output terpisah dibuat dari file tersimpan (ZIP)
- Perbaikan bug `in_array(): Argument #2`: parameter `?pelaksana=12` dan `?pelaksana[]=12` dinormalisasi menjadi array `$selectedPelaksanaIds` untuk view
- Refactor validasi status FPA ke service `App\Services\RequestStatusService` (konstanta `TRANSITIONS`, `validate()`: transisi + nomor FPA + checklist wajib untuk Dikirim ke PPK, `apply()`: set `tanggal_kirim_ppk`/`tanggal_selesai_spj`); `RequestStatusController@update` & `@updateAjax` memakai service
- Bulk-move Kanban FPA (`RequestStatusController@bulk`, route `POST requests/bulk/status`, name `requests.status.bulk`): per-FPA validasi via service, yang valid dipindah & yang gagal tetap (TANPA rollback all-or-nothing); respons `{results:{success:[{nomor_fpa,status,changed}], failed:[{nomor_fpa,errors}]}}`
- Perbaikan ordering route: `{id}` pada `requests.status.update` & `requests.status.ajax` diberi `->whereNumber('id')` agar `POST requests/bulk/status` tidak tertelan pola `requests/{id}/status`
- Kanban drag warning: kegagalan dipindah → card kembali ke kolom asal TANPA `location.reload()`; modal peringatan tampil ≥8 detik dengan tombol tutup + klik backdrop; menampilkan alasan + nomor FPA
- Automated testing baru: `SuperkendisPersistenceTest` (record+file tersimpan, regenerate tanpa duplikat, semua-pelaksana→Pengeluaran Riil Lengkap, Surat Tugas tak berubah, Perlu Perbaikan tak dioverwrite, jenis→jabatan, param pelaksana tunggal/array), `FpaStatusServiceBulkTest` (bulk valid/gagal parsial, transisi ilegal, bulk sukses)

### Checklist Detail Flow Refactor & Superkendis Improvement
- Refactor "Kelola Dokumen" (checklist detail): seluruh detail dokumen (SPD/SPPD, Pengeluaran Riil, Laporan Perjalanan) memakai daftar pelaksana bersumber dari Surat Tugas, bukan input manual `nama pelaksana`/`nomor surat tugas`/`jabatan`/data perjalanan (no re-input)
- Sumber data pelaksana di-resolve dari checklist "Surat Tugas" pada request yang sama (`stDetailFor()`), karena hanya checklist Surat Tugas yang memiliki `surat_tugas_detail` + `surat_tugas_pelaksanas`
- SPD/SPPD: hanya tabel daftar pelaksana (No | Nama | Nomor Surat Sub | Nomor Surat Tugas), tanpa form manual; `TravelDetail` dibangun ulang dari ST ketika data tersedia
- Perbaikan bug isi detail kosong: `syncTravelDetailFromSuratTugas()` tidak lagi membuat baris kosong (hanya bila ST pelaksana + nomor non-kosong); mengubah status checklist tidak membuat `real_expense_detail`/`travel_detail` kosong
- Pengeluaran Riil: tabel pelaksana (No | Nama Pelaksana | Nomor Surat Sub | Status | Aksi), Status = "Sudah Generate" (ada record `superkendis`) / "Belum Ada", Aksi = "Download" ke `file_docx` tersimpan; otomatis Lengkap saat semua pelaksana tergenerate
- Laporan Perjalanan: tabel pelaksana dengan checkbox bulk + status per-pelaksana (Sudah/Belum Mengumpulkan) disimpan ke tabel baru `travel_report_pelaksanas`; checklist hanya "Lengkap" bila SEMUA pelaksana "Sudah Mengumpulkan"
- Tabel baru `travel_report_pelaksanas` (id, checklist_id FK cascade, surat_tugas_pelaksana_id FK cascade, status default 'Belum Mengumpulkan', unique `(checklist_id, surat_tugas_pelaksana_id)`); model `App\Models\TravelReportPelaksana` (konstanta status) + relasi `SpjChecklist::travelReportPelaksanas()`
- Kanban Laporan Perjalanan: saat drag ke "Lengkap" tapi belum semua mengumpulkan, muncul modal "Konfirmasi Laporan Perjalanan" (checkbox pelaksana + status); simpan status dulu, baru terapkan Lengkap; batal menampilkan kembali card ke kolom asal tanpa reload (`ChecklistKanbanController@laporanPelaksana` GET + `storeLaporanPelaksana` POST, route `checklists.laporan-pelaksana`/`.store`)
- FPA status validation + bulk-move + warning Kanban tetap memakai `RequestStatusService` (tidak dirombak ulang; dipertahankan dari Sprint 13)
- Superkendis: lebar halaman `max-w-7xl`, kolom Status (Generated/Belum) + Aksi Download per baris; perilaku download: 1 pelaksana -> langsung download DOCX (tanpa ZIP/merge), >1 pelaksana -> merged/gabung atau ZIP terpisah sesuai method
- Automated testing: `DocumentDetailTest` diperbarui ke flow baru (travel detail diturunkan dari ST, tanpa baris detail kosong), `SuperkendisTest` (ZIP untuk >1 pelaksana, download langsung DOCX untuk 1 pelaksana)

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
superkendis          (id, surat_tugas_pelaksana_id, nip, kecamatan, tanggal_perjalanan, jenis_kegiatan, jabatan, file_docx, file_pdf)
sk_rate_perjalanan     (id, kecamatan, ibukota_kecamatan, besaran_biaya_transport, keterangan)
sk_rate_perjalanan_histories (id, sk_rate_perjalanan_id [nullable, nullOnDelete], data_sebelum, data_sesudah, aksi, user_id)
travel_details         (id, checklist_id, nomor_spd, nama_pelaksana, maksud_perjalanan, tempat_berangkat, tempat_tujuan, tanggal_berangkat, tanggal_kembali, transportasi)
real_expense_details   (id, checklist_id, nomor_surat_tugas, tanggal_surat_tugas, nama_pelaksana, jabatan, tanggal_kegiatan, uraian_pengeluaran, jumlah_pengeluaran, keterangan)
travel_reports         (id, checklist_id, nama_pelaksana, tujuan, uraian_kegiatan, tanggal_kegiatan, dokumentasi)
travel_report_pelaksanas (id, checklist_id, surat_tugas_pelaksana_id, status [default 'Belum Mengumpulkan'])  UNIQUE (checklist_id, surat_tugas_pelaksana_id)
templates              (id, nama_template, kategori, versi, file, status_aktif)
users                  (default Laravel users table)
```


---

## Sprint 15 - QA Bug Fix (Superkendis & Workflow Validation)
- BUG #1 (Critical): Page break antar pelaksana pada dokumen Superkendis gabungan. `SuperkendisController@appendBody` mengganti `<w:type w:val="continuous"/>` menjadi `nextPage` ketika menyisipkan section break sehingga antar pelaksana selalu berpindah halaman tanpa merusak tabel/border/tanda tangan (struktur XML lainnya dipertahankan utuh). Fallback `<w:br w:type="page"/>` bila sumber tidak memiliki sectPr.
- BUG #2 (High): Konsistensi validasi status. `RequestStatusService@requiredFields()` menjadi satu-satunya sumber aturan lapangan (Selesai -> `tanggal_selesai_spj`); dipakai dropdown (`update`), kanban (`updateAjax`), dan bulk (`bulk`). `catatan` optional di semua jalur. Kanban/bulk meneruskan `_auto_field_tanggal_selesai_spj` agar tanggal Selesai auto-fill hari ini; dropdown wajib mengisinya eksplisit. Pesan error user-friendly Bahasa Indonesia.
- BUG #3 (High): FPA hanya dapat diedit saat status "Persiapan" - guard server-side `RequestController@update()` (mirror `destroy()`).
- BUG #4 (Medium): Bulk select kartu Kanban FPA - checkbox tiap kartu + bar aksi bulk (Pilih Semua / status tujuan / Pindahkan) memakai endpoint `requests.status.bulk`, hasil sukses/gagal per FPA.
- BUG #5 (Medium): UX Laporan Perjalanan (popup konfirmasi Kanban, status Sudah/Belum Mengumpulkan per pelaksana dari Surat Tugas) - diverifikasi dari Sprint 14.
- BUG #6 (Medium): UX Superkendis - `alert()` diganti feedback in-page, tombol loading, tampilkan `session('error')`.
- BUG #7 (Medium): Verifikasi PDF DOMPDF (`Settings::PDF_RENDERER_DOMPDF` + path `vendor/dompdf/dompdf/src/Dompdf.php`).
- BUG #8 (Low): UI cleanup - query `ChecklistHistory` dipindah ke `RequestController@show()` (`$checklistHistory`), flash `session('error')` di dashboard & superkendis, lebar halaman checklist `max-w-7xl`.
- Automated testing: `RequestStatusTest` (parity Selesai dropdown/kanban, catatan optional, edit hanya Persiapan), `SuperkendisTest@test_bulk_merged_page_break_between_pelaksana` (unit reflection `appendBody`). Total 82 unit & feature tests PASS.

## Sprint 16 - Superkendis UX & Format Tanggal Global
- Kebijakan tanggal: input `<input type="date">` tetap (browser kirim `Y-m-d`, DB tidak berubah); YANG DIUBAH hanya teks tampilan menjadi `dd-mm-yyyy` (mis. `04-09-2026`); timestamp `d/m/Y H:i` → `d-m-Y H:i`. Tidak mengubah DB maupun nilai input.
- Daftar file teks tanggal diubah menjadi `d-m-Y` / `d-m-Y H:i`: `requests/show.blade.php`, `dashboard.blade.php`, `partials/status-workflow.blade.php`, `sk_rates/edit.blade.php`, `ChecklistKanbanController.php`, `CalendarController.php`.
- UX input NIP di `requests/superkendis.blade.php`: saat mengetik angka panjang, scroll horizontal input mengikuti posisi cursor (`input` → `scrollLeft = scrollWidth`); saat blur, scroll di-reset ke awal (`scrollLeft = 0`). Value tersimpan TIDAK diubah.
- Tabel Superkendis memakai `table-layout: fixed` + `<colgroup>` dengan min-width per kolom (Checkbox 50, Pelaksana 220, Nomor Surat Tugas 260, Kecamatan Tujuan 220, Tanggal Perjalanan 160, Jenis Kegiatan 200, NIP 180, Dokumen 180, Status 130, Action 120) + `overflow-x-auto` untuk scroll horizontal; select Kecamatan/Jenis Kegiatan menampilkan label lengkap saat dipilih.
- Automated testing: `SuperkendisTest@test_superkendis_index_page_renders` diperkuat (assert `table-layout: fixed`, `<colgroup>`, behavior NIP `scrollLeft`). Total 83 unit & feature tests PASS (269 assertions). Kode diformat ulang dengan Laravel Pint.
