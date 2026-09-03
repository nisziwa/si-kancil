# Audit Kondisi Implementasi Superkendis

## 1. Database & Relasi

**Tabel terkait (dari migrations):**

| Tabel | Isi | Relasi |
|---|---|---|
| `requests` | FPA (nomor_fpa, deskripsi, jenis_pengeluaran_id, status_spj, periode, tanggal, deadline_spj) | 1→* `spj_checklists` |
| `spj_checklists` | Baris checklist dokumen (nama_dokumen, status enum, file_path, is_required, urutan) | belongsTo `requests`; 1→1 detail |
| `surat_tugas_details` | Detail ST (nomor_surat_tugas, tanggal_surat_tugas, isi_tugas, **+ kolom teks `pelaksana` legacy nullable**) | hasOne dari `spj_checklists` (checklist_id); 1→* `surat_tugas_pelaksanas` |
| `surat_tugas_pelaksanas` | Individual pelaksana (nama_pelaksana, **nomor_surat**, urutan) | belongsTo `surat_tugas_details` |
| `spj_checklists.file_path` | Upload file dokumen (disimpan ke disk `public/spj-files`) | FileUploadController |

Relasi model: `SpjChecklist::suratTugasDetail()` (hasOne) → `SuratTugasDetail::pelaksanas()` (hasMany, diurut `urutan`). **Mendukung flow Superkendis** karena dari `checklist → suratTugasDetail → pelaksanas` mencukupi kebutuhan generate per pelaksana.

## 2. Surat Tugas

- **Model:** `SuratTugasDetail`, `SuratTugasPelaksana`
- **Controller:** `SpjChecklistController@edit/update` + `ChecklistKanbanController@updateStatus`
- **Route:** `PUT checklists/{id}` (form), `PATCH checklists/{id}/status` (kanban)
- **View:** `checklists/edit.blade.php` (section "Detail Surat Tugas")
- **Status:** enum 4 nilai `Belum Ada / Belum Lengkap / Lengkap / Perlu Perbaikan`; muncul di dropdown edit + kanban drag-drop.
- **Validasi Lengkap:** terpusat di `SuratTugasService` (baru): dropdown memvalidasi **input form sebelum persist**, kanban memvalidasi **data DB**. Syarat: Nomor, Tanggal, Isi Tugas, ≥1 pelaksana. Jika gagal → dropdown `back()->with('error')`, kanban JSON `422 success=false` + card di-revert.
- **Pengelolaan pelaksana:** admin/edit form tabel + "Tambah pelaksana" + input massal (textarea + pemisah + pratinjau + konfirmasi).

## 3. Pelaksana Surat Tugas

- **Multiple pelaksana** via tabel `surat_tugas_pelaksanas` (bukan lagi teks tunggal; kolom teks `pelaksana` legacy **tidak lagi ditulis** — hanya `syncPelaksana` yang dipakai).
- **Input:** form edit `pelaksana_nama[]` (tabel dinamis), satu-per-satu + massal (Baris/`;`/`,`).
- **Nomor surat sub otomatis:** `syncPelaksana()` + `buildSuratSubNumber()` → `B-1027/...` → `B-1027.1/...`, `.2`, `.3` (regenerate setiap simpan; delete-all lalu create).
- **Format penyimpanan:** satu row per pelaksana: `nama_pelaksana`, `nomor_surat` (sub), `urutan`.

## 4. Superkendis

- **Controller:** `SuperkendisController` (index, generate, bulk→separate/merged). **Tidak ada service generator terpisah** — semua logika (TemplateProcessor, cleanup XML, merge, PDF) hidup di controller.
- **Template DOCX:** `[template] Superkendis 2.docx` (storage/app/public).
- **Generate DOCX/PDF:** pakai `TemplateProcessor` (placeholder `{{ }}`), merge via XML `appendBody`, PDF via dompdf. Output: single download, ZIP terpisah, atau gabungan.
- **Halaman detail:** `requests/superkendis.blade.php` — tabel pelaksana + checkbox + input per pelaksana (kecamatan, tanggal, NIP), pilih format (DOCX/PDF) & metode (pisah/gabung). Pintu masuk: tombol "Generate Superkendis" + tabel di `requests/show.blade.php` (hanya tampil jika `$stReady` → checklist Surat Tugas **Lengkap** + detail + ≥1 pelaksana).
- **Upload hasil generate:** **TIDAK ADA.** `SuperkendisController` hanya `response()->download(...)` (deleteFileAfterSend). Hasil tidak disimpan ke `file_path` dan **tidak dihubungkan ke checklist dokumen**.

## 5. Pengeluaran Riil + Surat Non Kendaraan Dinas

- **File:** `RealExpenseDetail` model, migrasi `real_expense_details`, controller `SpjChecklistController@update` (blok `Pengeluaran Riil`), view `edit.blade.php` (section "Detail Pengeluaran Riil & Surat Non Kendaraan Dinas").
- **Fungsi halaman:** form isian SENDIRI (real_nomor_surat_tugas, real_tanggal, real_nama_pelaksana, real_jabatan, real_tanggal_kegiatan, uraian, jumlah, keterangan) yang di-`updateOrCreate` per checklist.
- **Kunci temuan:** **Membuat data BARU** (`RealExpenseDetail`), **TIDAK memanfaatkan** `surat_tugas_pelaksanas` yang sudah ada; meski mengisi "Nomor Surat Tugas" manual. Ini DUPLIKAT input nama/nomor dari Surat Tugas. (*Catatan dari konteks: Sprint 12 sengaja TIDAK menambahkan form detail baru ini saat generate.*)

## 6. Gap Analysis

**Target ideal:** `Surat Tugas → Validasi Lengkap → Generate Superkendis → DOCX/PDF → Checklist Dokumen`

| # | Kondisi Saat Ini | File Penting | Gap vs Target | Urutan Implementasi Disarankan |
|---|---|---|---|---|
| 1 | Surat Tugas + detail + pelaksana **✓** | `SpjChecklistController`, `edit.blade.php`, models | Minimal | (sudah ada) |
| 2 | Validasi Lengkap terpusat (dropdown + kanban) **✓** | `SuratTugasService`, 2 controller | Minimal | (sudah ada) |
| 3 | Generate Superkendis per/bulk pelaksana **✓** | `SuperkendisController`, `superkendis.blade.php` | **Generate sudah ada tapi TIDAK disimpan ke checklist** | — |
| 4 | Output DOCX/PDF **✓** | `SuperkendisController` (TemplateProcessor/dompdf) | Minimal | (sudah ada) |
| 5 | **DOCX/PDF → Checklist Dokumen** **✗** | `FileUploadController` (umum), `SuperkendisController` | **GAP TERBESAR:** hasil generate tidak tersimpan ke `spj_checklists.file_path` / tidak ada checklist "Dokumen Superkendis" hasil generate; tidak ada otomatisasi `upload` | Perlu: simpan hasil generate ke disk + tautkan ke checklist (upload/resimpan), atau buat atribut file Superkendis per pelaksana |

**Gap utama & rekomendasi:**

1. **Otomatisasi penyimpanan hasil generate** — alihkan dari `download` murni menjadi simpan file (disk `public`/`spj-files`) lalu set `file_path` checklist (atau relasi file-per-pelaksana), sehingga hasil Superkendis tercatat di Checklist Dokumen. Bisa via integrasi `FileUploadController` / upload otomatis pasca-generate.
2. **Data Superkendis persisten** — `superkendis.blade.php` meminta kecamatan/tanggal/NIP per pelaksana tiap generate (tidak disimpan). Pertimbangkan simpan masukan ini (e.g. ke tabel Superkendis/hasil) untuk riwayat & regenerate tanpa isi ulang.
3. **Pengeluaran Riil & Surat Non Kendaraan Dinas** — saat ini duplikat input manual dari Surat Tugas/pelaksana. Idealnya **ambil dari `surat_tugas_pelaksanas`** (nama + nomor sub otomatis), bukan input ulang — menghindari data tak konsisten.
