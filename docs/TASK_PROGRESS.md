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

## Sprint 10 — Master SK Rate Management & Peningkatan Generate Superkendis
Status: Completed
Tanggal Selesai: 2026-09-03
Ringkasan Perubahan:
- Halaman management SK Rate Perjalanan baru (index dengan pencarian/filter, create, edit, destroy) + menu navigasi "SK Rate"
- Tabel & model baru `sk_rate_perjalanan_histories` untuk riwayat perubahan SK Rate (data sebelum/sesudah, aksi create/update/delete, user, waktu); FK `nullOnDelete` agar riwayat tetap tersimpan walau rate dihapus
- Rework halaman generate Superkendis: pilihan pelaksana via checkbox + input per pelaksana (kecamatan tujuan, tanggal perjalanan, NIP) menggantikan form berbagi satu set input
- Export Superkendis terpilih: format DOCX/PDF, metode Pisah ZIP / Gabung satu file; setiap pelaksana terpilih wajib isi kecamatan & tanggal
- Generate dokumen berbasis template `TemplateProcessor` dengan `storage/app/public/[template] Superkendis.docx` (placeholder `{{...}}`), bukan hardcode
- Placeholder didukung: nama, NIP, nomor & tanggal surat tugas, tanggal perjalanan, biaya transport (dari SK Rate), terbilang, jenis perjalanan, jabatan; cleanup placeholder yang membungkus field Word/MERGEFIELD
- Helper baru `App\Support\Terbilang` untuk konversi angka → kata
- Integrasi SK Rate: besaran biaya transport & terbilang diambil dari kecamatan tujuan sesuai SK Rate
- DatabaseSeederTest diperbaiki: verifikasi seeder (18 SK Rate, tanpa dummy lama); SuperkendisTest diperbarui ke alur per-pelaksana (checkbox)
- Automated feature testing: SuperkendisTest (per-pelaksana, bulk merged/separate, validasi) & SkRatePerjalananTest (CRUD, search, history create/update/delete)
- Verifikasi menyeluruh: seluruh 55 unit & feature tests berhasil 100% (PASS)

## Sprint 11 — Document Generation Improvement
Status: Completed
Ringkasan Perubahan:
- Memperbaiki generate DOCX berbasis template agar hasil final mempertahankan layout Word asli
- Flow generate diubah agar langsung: `Template DOCX → TemplateProcessor → DOCX final`
- Menghapus proses merusak yang sebelumnya dilakukan (`IOFactory::load()` + salin elemen ke PhpWord baru) yang menyebabkan tabel hilang, border hilang, alignment berubah, dan tanda tangan berantakan
- Memastikan tabel (Daftar Pengeluaran Riil), border, alignment, dan tanda tangan tetap sesuai template; semua placeholder tetap terganti
- Export PDF: load hasil DOCX (struktur utuh) lalu konversi langsung ke PDF
- Gabung (merged) Superkendis: isi template per pelaksana lalu gabungkan isi `<w:body>` per dokumen agar tabel/border/style tetap utuh
- Verifikasi: seluruh 55 unit & feature tests berhasil 100% (PASS)

## Sprint 11b — Document Generation Improvement v2
Status: Completed
Ringkasan Perubahan:
- Mengganti template Superkendis menjadi `storage/app/public/[template] Superkendis 2.docx` (`TEMPLATE_PATH = '[template] Superkendis 2.docx'`)
- Standardisasi placeholder tetap memakai `{{ }}` (`setMacroChars('{{','}}')`), bukan `${}`; mapping placeholder v2: `{{nama}}`, `{{NIP}}`, `{{nomor surat tugas}}`, `{{tanggal surat tugas}}`, `{{tanggal perjalanan}}`, `{{biaya sk}}`, `{{terbilangnya berapa}}`, `{{jenis kegiatan}}`, `{{jabatan}}`
- Pemetaan jenis kegiatan → jabatan: Pelatihan → PCL, Pendataan Lapangan → PCL, Pengawasan Lapangan → PML, Supervisi Lapangan → Supervisor
- Semua tanggal generate dalam format Indonesia (mis. "25 Juli 2026"), bukan `Y-m-d` / `d-m-Y`
- `cleanupTemplate` digeneralisasi: setelah penggabungan run, semua placeholder `{{key}}` diganti dari data (menangani `{{jenis kegiatan}}` yang terpecah antar-run)
- Layout tetap dipertahankan: tabel Daftar Pengeluaran Riil muncul, border sesuai template, tanda tangan benar, nama tidak menempel teks sebelumnya, semua placeholder terganti
- Verifikasi: seluruh 55 unit & feature tests berhasil 100% (PASS)

## Sprint 12 — Surat Tugas Completion Validation & Superkendis Flow
Status: Completed
Ringkasan Perubahan:
- Service terpusat baru `App\Services\SuratTugasService` untuk validasi kelengkapan Surat Tugas (Nomor Surat Tugas, Tanggal Surat Tugas, Isi Tugas, minimal 1 Pelaksana) — dipakai bersama dropdown & kanban agar logic tidak terduplikasi
- Validasi dropdown (`SpjChecklistController@update`): meminta status "Lengkap" saat data belum lengkap → perubahan dibatalkan + pesan kekurangan data
- Validasi kanban (`ChecklistKanbanController@updateStatus`): dipindah ke "Lengkap" saat gagal → `success=false` (HTTP 422), card kembali ke kolom semula + notifikasi
- Tombol "Generate Superkendis" di Detail FPA hanya muncul jika Surat Tugas berstatus Lengkap
- Input pelaksana massal pada form Surat Tugas: textarea + pemisah (Baris baru / `;` / `,`) + pratinjau bernomor + konfirmasi; tetap ada "Tambah Pelaksana satu per satu"
- Nomor surat sub otomatis dipertahankan (`B-1041/75040/KP.650/2026` → `.1/.2/.3`)
- Generate Superkendis membaca data dari Daftar Pelaksana Surat Tugas; tidak ada form detail baru
- Automated feature testing baru: `SuratTugasValidationTest` (tanpa nomor/tanggal/uraian/pelaksana tidak bisa Lengkap, dropdown tervalidasi, kanban tervalidasi, tombol hanya muncul bila lengkap, input massal pelaksana, nomor sub otomatis)
- Verifikasi menyeluruh: seluruh 66 unit & feature tests berhasil 100% (PASS)

## Sprint 13 — Superkendis Flow Improvement & FPA Kanban Enhancement
Status: Completed
Ringkasan Perubahan:
- Tabel baru `superkendis` (migrasi `2026_09_03_091949_create_superkendis_table.php`): satu record per `surat_tugas_pelaksana_id` dengan `nip, kecamatan, tanggal_perjalanan, jenis_kegiatan, jabatan, file_docx, file_pdf` (NO `checklist_id`/`nomor_surat` snapshot/`generated_at`)
- Model baru `App\Models\Superkendis` (fillable + cast tanggal_perjalanan date + relasi `belongsTo SuratTugasPelaksana`); `SuratTugasPelaksana` ditambah relasi `hasOne superkendis`
- Persistensi per-pelaksana: `generate()` & `bulk()` menyimpan file (DOCX/PDF) ke `storage/app/public/spj-files/superkendis/...` dan merekam/memperbarui record `superkendis` via `update`/`create` pada `surat_tugas_pelaksana_id` (regenerate tidak membuat duplikat)
- Checklist "Pengeluaran Riil + Surat Non Kendaraan Dinas" otomatis jadi "Lengkap" HANYA saat SEMUA pelaksana telah tersimpan Superkendis-nya, dan target tidak berstatus "Perlu Perbaikan"/"Lengkap"; mencatat `ChecklistHistory`; Surat Tugas & checklist lain tidak pernah disentuh
- Jenis kegiatan = daftar statis (`Pelatihan/Pendataan Lapangan→PCL, Pengawasan Lapangan→PML, Supervisi Lapangan→Supervisor`); jabatan tidak diinput manual
- Perbaikan bug `in_array(): Argument #2 (~?pelaksana=`)`: param dinormalisasi menjadi array `selectedPelaksanaIds` untuk view
- Form Superkendis dipertahankan tanpa form baru; nama pelaksana + nomor surat sub readonly, prefill dari record `superkendis` saat regenerate; link dokumen tersimpan ditampilkan
- File gabungan (merged) tetap sebagai output tambahan; output terpisah dibuat dari file tersimpan (ZIP)
- Refactor validasi status FPA ke `App\Services\RequestStatusService` (TRANSITIONS terpusat, `validate()`, `apply()`); `RequestStatusController` menggunakan service untuk `update`/`updateAjax`
- Bulk Kanban FPA (`RequestStatusController@bulk`, route `requests.status.bulk`) → per-FPA validasi via service, valid dipindah & gagal tetap; response `{results:{success:[{nomor_fpa,...}], failed:[{nomor_fpa,errors}]}}`; tanpa rollback all-or-nothing
- Kanban drag warning: pada kegagalan card kembali ke kolom asal TANPA `location.reload()`, modal ≥8 detik dengan tombol tutup + klik backdrop, menampilkan alasan + nomor FPA
- Automated feature testing baru: `SuperkendisPersistenceTest` (record+file tersimpan, regenerate tanpa duplikat, semua-pelaksana→Pengeluaran Riil Lengkap, Surat Tugas tak berubah, Perlu Perbaikan tak dioverwrite, jenis→jabatan, param pelaksana tunggal/array) & `FpaStatusServiceBulkTest` (bulk valid/gagal parsial, transisi ilegal, bulk sukses)
- Verifikasi menyeluruh: seluruh 75 unit & feature tests berhasil 100% (PASS)

## Sprint 14 - Checklist Detail Flow Refactor & Superkendis Improvement
Status: Completed
Ringkasan Perubahan:
- Refactor "Kelola Dokumen" (checklist detail) sehingga seluruh detail dokumen (SPD/SPPD, Pengeluaran Riil, Laporan Perjalanan) memakai daftar pelaksana yang bersumber dari Surat Tugas (tidak ada input manual ulang nama pelaksana / nomor surat tugas / jabatan / data perjalanan)
- Sumber pelaksana di-resolve dari checklist "Surat Tugas" pada request yang sama via helper `stDetailFor()` di `SpjChecklistController` (hanya checklist Surat Tugas yang memiliki `surat_tugas_detail`); `edit()` mengirim `$stDetail` & `$stPelaksanas` ke view
- SPD/SPPD: hanya tabel daftar pelaksana (No | Nama | Nomor Surat Sub | Nomor Surat Tugas) tanpa form manual; `TravelDetail` dibangun ulang dari sumber ST saat data tersedia
- Perbaikan bug isi detail kosong: `syncTravelDetailFromSuratTugas()` tidak membuat baris kosong (hanya bila pelaksana + nomor non-kosong); mengubah status checklist tidak menghasilkan `real_expense_detail`/`travel_detail` kosong
- Pengeluaran Riil: tabel pelaksana (No | Nama | Nomor Surat Sub | Status | Aksi); Status "Sudah Generate" (ada record `superkendis`) / "Belum Ada"; Aksi "Download" mengarah ke `file_docx` tersimpan; otomatis Lengkap saat semua pelaksana tergenerate
- Laporan Perjalanan: tabel pelaksana dengan checkbox bulk + status per-pelaksana; disimpan ke tabel baru `travel_report_pelaksanas` (migrasi `2026_09_03_100810_create_travel_report_pelaksanas_table.php`, model `App\Models\TravelReportPelaksana`); checklist hanya "Lengkap" bila SEMUA pelaksana "Sudah Mengumpulkan"
- Kanban Laporan Perjalanan: drag ke "Lengkap" saat belum semua mengumpulkan -> modal "Konfirmasi Laporan Perjalanan" (`ChecklistKanbanController@laporanPelaksana`/`storeLaporanPelaksana`, route `checklists.laporan-pelaksana`/`.store`); status disimpan dulu baru status Lengkap diterapkan; batal menampilkan kembali card ke kolom asal tanpa reload
- FPA status validation + bulk-move + peringatan Kanban dipertahankan memakai `RequestStatusService` (dari Sprint 13)
- Superkendis: lebar halaman `max-w-7xl`; kolom Status (Generated/Belum) + Aksi Download per baris; perilaku download: 1 pelaksana -> langsung download DOCX (tanpa ZIP/merge), >1 pelaksana -> merged/gabung atau ZIP terpisah sesuai method
- Automated feature testing: `DocumentDetailTest` diperbarui (travel detail diturunkan dari ST, tidak ada baris detail kosong) dan `SuperkendisTest` (ZIP untuk >1 pelaksana, download langsung DOCX untuk 1 pelaksana)
- Verifikasi menyeluruh: seluruh 76 unit & feature tests berhasil 100% (PASS)

## Sprint 15 - QA Bug Fix (Superkendis & Workflow Validation)
Status: Completed
Ringkasan Perubahan:
- BUG #1 (Critical): Page break antar pelaksana pada dokumen Superkendis gabungan. `SuperkendisController@appendBody` menyisipkan paragraf page-break eksplisit `<w:p><w:r><w:br w:type="page"/></w:r></w:p>` di antara blok pelaksana (bukan `<w:sectPr>` yang tidak valid di tengah `<w:body>`), sehingga antar pelaksana selalu berpindah halaman tanpa merusak tabel/border/tanda tangan (struktur XML sisanya dipertahankan).
- BUG #2 (High): Konsistensi validasi status antara dropdown, kanban (ajax), dan bulk. Aturan lapangan (`tanggal_selesai_spj` untuk Selesai) kini terpusat di `App\Services\RequestStatusService @requiredFields()` yang dipakai ketiga jalur; `catatan` optional di semua jalur; kanban/bulk menandai `_auto_field_tanggal_selesai_spj` agar tanggal Selesai terisi otomatis hari ini, dropdown wajib mengisinya eksplisit; pesan error user-friendly Bahasa Indonesia.
- BUG #3 (High): FPA hanya dapat diedit saat berstatus "Persiapan". Guard server-side ditambahkan di `RequestController@update()` (mirror `destroy()`), view tombol Edit sudah terkondisi oleh status.
- BUG #4 (Medium): Bulk select kartu Kanban FPA. Checkbox pada tiap kartu + bar aksi bulk (Pilih Semua / status tujuan / Pindahkan) memakai endpoint `requests.status.bulk` yang sama, dengan hasil sukses/gagal per FPA beserta alasannya.
- BUG #5 (Medium): Laporan Perjalanan UX - popup konfirmasi Kanban dengan status Sudah/Belum Mengumpulkan per pelaksana, data bersumber dari Surat Tugas (sudah ada di Sprint 14, diverifikasi).
- BUG #6 (Medium): Superkendis UX - `alert()` diganti feedback in-page, tombol loading "Memproses...", tampilkan `session('error')`, konsistensi pesan.
- BUG #7 (Medium): PDF (DOMPDF) verifikasi - `Settings::setPdfRendererName(PDF_RENDERER_DOMPDF)` + path ke `vendor/dompdf/dompdf/src/Dompdf.php` diverifikasi via tes generate PDF.
- BUG #8 (Low): UI cleanup - raw Eloquent query di `show.blade.php` dipindah ke `RequestController@show()` (variabel `$checklistHistory`), flash `session('error')` ditambahkan di dashboard & superkendis, konsistensi lebar halaman checklist (`max-w-7xl`), gaya modal/button seragam.
- Automated testing baru: `RequestStatusTest` (parity dropdown/kanban Selesai, catatan optional, edit hanya Persiapan) dan `SuperkendisTest@test_bulk_merged_page_break_between_pelaksana` (unit via reflection pada `appendBody`). Seluruh 82 unit & feature tests berhasil 100% (PASS). Kode diformat ulang dengan Laravel Pint.

### Sprint 15 - Follow-up Feedback (Superkendis UX)
Status: Completed
Ringkasan Perubahan (atas 5 keluhan user setelah Sprint 15):
- #1 Status "Generated" kini tampil langsung tanpa refresh manual: setelah berhasil unduh blob di `resources/views/requests/superkendis.blade.php`, `refreshAfterSuccess()` menyimpan pesan di `sessionStorage` lalu reload halaman setelah ~1500ms sehingga status per pelaksana langsung "Generated".
- #2 Double download dihilangkan: handler submit `superkendis.blade.php` memanggil `e.preventDefault()` di awal handler (sebelum `fetch`), mencegah submit normal + unduh blob terjadi bersamaan.
- #3 Page break pada dokumen Word gabungan diperbaiki permanen via paragraf page-break eksplisit di `appendBody` (lihat BUG #1) - diuji `SuperkendisTest@test_bulk_merged_page_break_between_pelaksana` memastikan `w:type="page"` mendahului konten pelaksana berikutnya.
- #4 Isian sebelumnya (kecamatan/lokasi, tanggal perjalanan, NIP, jenis kegiatan) langsung tampil saat membuka halaman Superkendis: baris pelaksana yang sudah punya record `superkendis` otomatis tercentang dan field terisi data terakhir, tetap editable untuk regenerate. Regenerate memakai `update`/`create` per `surat_tugas_pelaksana_id` (tanpa duplikat).
- #5 Border PDF tidak lagi seluruhnya tebal: PhpWord writer HTML menyuntikkan CSS global `table {border: 1px solid black}` & `td {border: 1px solid black}` yang membuat SEMUA tabel/ sel bergaris tebal di PDF. Ditangani via API resmi `setEditCallback()` pada `SuperkendisController@pdfWriter` (dipakai `downloadFinal` & `writeDocument`) → `stripDefaultTableBorders()` menghapus border default tsb, mengaktifkan `border-collapse: collapse`, dan mempertahankan border asli template (hanya tabel yang memang perlu garis, 0.2pt).
- Automated testing: test baru `SuperkendisTest@test_index_autochecks_pelaksana_with_stored_superkendis_and_prefills` (centang otomatis + prefill) dan verifikasi `stripDefaultTableBorders`/`editCallback` (border default hilang, border template dipertahankan, PDF tetap ter-generate). Seluruh 83 unit & feature tests berhasil 100% (PASS). Kode diformat ulang dengan Laravel Pint.

## Indonesian Date Display Standard
Status: Completed
Ringkasan Perubahan:
- Standar tampilan tanggal **Indonesia `dd MMMM yyyy`** (mis. `02 September 2026`) menggantikan `d-m-Y`/`d/m/Y` agar tidak ambigu; database tetap `Y-m-d`, tipe kolom `date` tidak diubah, dan input `<input type="date">` tetap mengirim `Y-m-d` (hanya teks tampilan yang diformat).
- Formatter tanggal terpusat baru `App\Support\Tanggal` (`Tanggal::format` → `dd MMMM yyyy`, `Tanggal::formatDateTime` → `dd MMMM yyyy HH:mm`), menerima Carbon/DateTime/string, dengan fallback; pola mengikuti `App\Support\Terbilang`. Tidak ada formatter berulang di tiap Blade.
- Diterapkan ke: `requests/show.blade.php` (periode & deadline), `dashboard.blade.php` (deadline kanban & tabel), `partials/status-workflow.blade.php` (timeline status SPJ), `partials/kanban-checklist.blade.php` + `ChecklistKanbanController` (timestamp riwayat checklist via AJAX), `sk_rates/edit.blade.php` (riwayat SK Rate), `CalendarController` (deadline kalender), dan `SuperkendisController` (tanggal surat tugas & perjalanan pada generate DOCX/PDF).
- Label/helper input diperbarui ke format Indonesia (contoh: "02 September 2026") tanpa mengubah input/persistensi.
- Automated testing baru: `TanggalTest` (format nama bulan, Carbon, fallback kosong/tidak valid, format datetime). Seluruh unit & feature tests PASS.

## Sprint 16 - Superkendis UX & Format Tanggal Global
Status: Completed
Ringkasan Perubahan:
- Kebijakan tanggal: input `<input type="date">` tetap (browser/kirim nilai `Y-m-d`, DB tidak diubah); yang diubah hanya teks tampilan menjadi `dd-mm-yyyy` (mis. `04-09-2026`), timestamp `d/m/Y H:i` → `d-m-Y H:i`.
- File teks tanggal diubah menjadi `d-m-Y` / `d-m-Y H:i`: `resources/views/requests/show.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/partials/status-workflow.blade.php`, `resources/views/sk_rates/edit.blade.php`, `app/Http/Controllers/ChecklistKanbanController.php`, `app/Http/Controllers/CalendarController.php`.
- UX input NIP di `resources/views/requests/superkendis.blade.php`: saat mengetik angka panjang, scroll horizontal mengikuti posisi cursor (`input` → `scrollLeft = scrollWidth`); saat blur, scroll di-reset ke awal (`scrollLeft = 0`). Value tersimpan TIDAK diubah.
- Tabel Superkendis memakai `table-layout: fixed` + `<colgroup>` dengan min-width per kolom (Checkbox 50, Pelaksana 220, Nomor Surat Tugas 260, Kecamatan Tujuan 220, Tanggal Perjalanan 160, Jenis Kegiatan 200, NIP 180, Dokumen 180, Status 130, Action 120) + `overflow-x-auto` untuk scroll horizontal; select Kecamatan/Jenis Kegiatan menampilkan label lengkap saat dipilih.
## Sprint 17 - Flatpickr Datepicker & Range Picker FPA
Status: Completed
Ringkasan Perubahan:
- Flatpickr Datepicker diterapkan secara global menggantikan `<input type="date">`.
- Tampilan form akan menunjukkan format Indonesia (misal: "2 September 2026") tetapi akan mengirim `Y-m-d` ke server melalui hidden inputs.
- FPA `tanggal_mulai` dan `tanggal_selesai` digabung menjadi range picker untuk input yang lebih sederhana dan UX yang lebih baik.
- Penyesuaian pada file form: `layouts/app.blade.php`, `requests/create.blade.php`, `requests/edit.blade.php`, `checklists/edit.blade.php`, `partials/status-workflow.blade.php`, `requests/superkendis.blade.php`.

## Sprint 18 - Laporan Perjalanan Workflow, Bulk Checklist Action, dan Integrasi POK (Issue #13)
Status: Completed
Ringkasan Perubahan:
- **Popup Konfirmasi Kanban diperbaiki**: drag Laporan Perjalanan ke "Lengkap" saat belum semua pelaksana mengumpulkan tidak lagi langsung diubah; endpoint `ChecklistKanbanController@updateStatus` mengembalikan `require_confirmation=true` + `not_collected` (jumlah) + pesan ramah "Terdapat X pelaksana yang belum mengumpulkan laporan perjalanan." Popup baru (Batal / Lengkapi Laporan -> `/checklists/{id}/edit`) di `partials/kanban-checklist.blade.php`. Bila semua sudah terkumpul, drag langsung berhasil menjadi Lengkap.
- **Bulk action checklist (Kanban)**: toolbar "Bulk Checklist" di `resources/views/requests/show.blade.php` + checkbox pada tiap kartu kanban memanggil endpoint `POST /requests/{id}/checklists/bulk-status` (`ChecklistKanbanController@bulkStatus`). Setiap item divalidasi sama seperti perubahan individu; item gagal (mis. Laporan belum terkumpul) dilaporkan terpisah, tanpa mengubah status yang valid.
- **Workflow per pelaksana**: pada `checklists/edit.blade.php`, seksi Laporan Perjalanan kini mendukung aksi massal (toolbar luar tabel) + per-baris: memilih pelaksana, mengubah status, mengunggah, atau membuat (generate) laporan. Status disimpan per pelaksana di tabel `travel_report_pelaksanas` (`TravelReportPelaksanaController::bulkPelaksanaStatus`, `TravelReportController@upload`, `updateStatus`).
- **Sinkronisasi status**: `TravelReportController@syncChecklistStatus` menurunkan checklist dari Lengkap menjadi Belum Lengkap bila ada pelaksana yang berubah menjadi belum mengumpulkan. Mengunggah/membuat laporan otomatis menandai pelaksana "Sudah Mengumpulkan".
- **Konfirmasi revert**: mengembalikan status ke "Belum Mengumpulkan" saat sudah ada file laporan wajib konfirmasi (server `require_confirm=true` pada `updateStatus` kecuali `confirm=true` dikirim).
- **Master POK**: tabel baru `master_program`, `master_kegiatan`, `master_output`, `master_sub_output`, `master_komponen`, `master_akun`, `master_rincian_pok` (migrasi `2026_09_03_225143_create_master_pok_tables.php`) + model `MasterProgram`, `MasterKegiatan`, `MasterOutput`, `MasterSubOutput`, `MasterKomponen`, `MasterAkun`, `MasterRincianPok` (relasi berantai). Data contoh pada `PokSeeder` (054.01.GG / 2897 / 2897.BMA / 2897.BMA.005 / 005 / 521213).
- **Tabel `travel_reports` direstrukturisasi** (migrasi `2026_09_03_225143_add_pok_and_report_fields_to_travel_reports.php`): kolom `fpa_id`, `surat_tugas_pelaksana_id`, `jenis_laporan` (Pendataan / Pengawasan; konstanta `TravelReport::JENIS_*`), `judul_laporan`, `tanggal_laporan`, `pok_rincian_id` (nullable), `file_docx`, `file_pdf`.
- **Generate laporan**: `TravelReportService` membuat DOCX/PDF via PhpWord (Times New Roman 12, header terpusat jenis laporan, seksi identitas/judul/tanggal/pembiayaan). Data pembiayaan diambil dari rantai POK (`buildData` -> `pokLines`) tanpa template file eksternal.
- **Pencarian POK**: autocomplete `GET /travel-reports/pok/search` + `GET /travel-reports/pok/detail/{id}` dengan rantai Program-Kegiatan-Output-Sub Output-Komponen-Akun.
- **Pesan validasi ramah**: validasi generate (jenis/judul/tanggal/POK) kini memakai pesan Bahasa Indonesia spesifik.
- **Automated testing**: `tests/Feature/TravelReportPOKTest.php` (13 kasus) mencakup relasi POK, pencarian POK, popup kanban (jumlah belum terkumpul + semua terkumpul), bulk checklist gagal, bulk pelaksana, upload -> Sudah, revert perlu konfirmasi (+ dengan konfirmasi), generate dengan POK, validasi generate, isi pembiayaan dari POK, dan output DOCX. Seluruh 100 testes (87 lama + 13 baru) PASS.
- **Bug yang diperbaiki**: resolusi pelaksana Laporan Perjalanan kini memakai checklist "Surat Tugas" pada request yang sama (`stDetailFor`) karena checklist Laporan tidak memiliki `suratTugasDetail` sendiri; dan bug `$format` null saat generate (default docx).

## Master POK Integration for Travel Report (Issue #13)
Status: Completed
- Struktur tabel POK: `master_program` <- `master_kegiatan` <- `master_output` <- `master_sub_output` <- `master_komponen`; `master_akun` berdiri sendiri. `master_rincian_pok` menyimpan `program_id`, `kegiatan_id`, `output_id`, `sub_output_id`, `komponen_id`, `akun_id` + kolom teks `rincian` (diindeks).
- Relasi master: program 1-N kegiatan, kegiatan 1-N output, output 1-N sub output, sub output 1-N komponen; model `MasterProgram`, `MasterKegiatan`, `MasterOutput`, `MasterSubOutput`, `MasterKomponen`, `MasterAkun`, `MasterRincianPok` (relasi berantai, `pokRincian` di TravelReport).
- Rincian pencarian: field "POK / Pembiayaan" pada generate laporan memanggil `GET /travel-reports/pok/search` -> `MasterRincianPok::where('rincian','like',...)` dengan eager-load rantai Program-Kegiatan-Output-Sub Output-Komponen-Akun.
- Mapping kegiatan: 1 program bersama `054.01.GG` + 1 akun `521213`; 3 kegiatan baru -> 8130 (Sumber Daya Hayati), 8131 (Mineral & Konstruksi), 2904 (Industri); tiap kegiatan punya output `{kode}.BMA`, sub output sendiri, komponen `005`. 9 rincian honor pendataan (KSA, SKP, Ubinan, SKGB, konstruksi, pertambangan, Captive Power, industri besar sedang, industri mikro kecil).
- Penggunaan pada generate laporan perjalanan: nilai POK terpilih disimpan di `travel_reports.pok_rincian_id` lalu dibaca `TravelReportService->pokLines()` untuk mengisi bagian "5. Pembiayaan Kegiatan" pada DOCX/PDF.
- Seeder idempotent (`updateOrCreate` dalam transaksi DB), aman dijalankan berulang; sudah di-wire ke `DatabaseSeeder`. 100 tests PASS.

## Hotfix - Laporan Perjalanan status save (validation.in & bulk connection)
Status: Completed
- Perbaiki `SpjChecklistController@update` validasi nested: `report_status.*` -> `report_status.status.*` (nilai skalar) sehingga simpan "Sudah Mengumpulkan" via form utama tidak lagi memicu `validation.in`.
- Perbaiki `TravelReportController@bulkPelaksanaStatus` agar mengembalikan JSON (bukan redirect `back()`) sehingga aksi bulk tidak lagi gagal parsing JSON ("Terjadi kesalahan koneksi").
- Tambah regression test `test_main_form_saves_sudah_mengumpulkan_without_validation_in_error`; 101 tests PASS.

## Laporan Perjalanan Save Action Simplification (Issue #14)
Status: Completed
- Masalah: halaman *Kelola Dokumen — Laporan Perjalanan* memiliki dua tombol simpan yang membingungkan: "Simpan Perubahan" (bagian Detail Laporan Perjalanan) dan "Simpan Semua Perubahan" (footer).
- Penyebab: kedua tombol adalah `type="submit"` di dalam form utama yang sama (`checklists.update`), sehingga keduanya melakukan submit form yang identik tanpa perbedaan fungsi.
- Solusi: menghapus tombol simpan duplikat di bagian Detail Laporan Perjalanan dan mempertahankan satu tombol simpan di footer; label diubah menjadi "Simpan Perubahan" (kata "Semua" tak lagi diperlukan).
- Hasil akhir: hanya satu tombol simpan pada halaman. Tidak ada perubahan backend/endpoint — checkbox pelaksana, dropdown status, bulk action, upload, dan generate laporan tetap berjalan.
- Verifikasi: 101 tests PASS; `view:cache` sukses.


## Root Fix - Save button tidak jalan pada Laporan Perjalanan (Issue #14)
Status: Completed
- Masalah lanjutan: setelah tombol simpan dirapikan menjadi satu, tombol simpan (footer) ternyata tidak berfungsi saat diklik.
- Akar penyebab: form generate laporan (`#generate-form`) berada *di dalam* form utama (`checklists.update`) yang sama — nested `<form>` tidak valid di HTML dan menyebabkan browser menutup form utama lebih awal, sehingga tombol "Simpan Perubahan" (footer, yang berada setelah form generate) jatuh di luar form dan tidak melakukan submit.
- Solusi: memindahkan modal Generate Laporan beserta `#generate-form` keluar dari form utama (ditempatkan setelah `</form>`), dibungkus `@if` Laporan Perjalanan yang sama. Form utama kini bersih tanpa nested form, dan tombol "Simpan Perubahan" footer berfungsi normal.
- Dampak: generate tetap berjalan (dikirim via fetch + CSRF header), tidak ada perubahan endpoint/logic backend. Verifikasi struktur: form utama (baris 27-377) berisi tombol simpan; `#generate-form` (387-428) di luar form utama. 101 tests PASS.


---

## Regression Test - Save Button in Main Form (Issue #14 root fix)
- Added permanent regression test 	ests/Feature/ChecklistSaveButtonTest.php that renders the Laporan Perjalanan edit page and asserts (via DOMDocument, mimicking browser parsing):
  - exactly ONE "Simpan Perubahan" button exists,
  - that button is a POST submit located INSIDE the main checklists/{id} form (not inside the generate form, not outside any form).
- Guards against the nested <form> bug that made the footer save button silently do nothing.
- Full suite: 102 tests PASS (342 assertions).

## Hard refresh note for "simpan perubahan ga jalan"
- The current blade structure is verified correct (DOMDocument confirms the single save button is inside the main form; only status is required and always has a value; backend save covered by tests).
- If the button still appears unresponsive in the browser, it is the STALE page from before commit c9cbffa — do a hard refresh (Ctrl+F5) or clear the site cache.
