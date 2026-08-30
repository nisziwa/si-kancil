# SI-KANCIL
## Sistem Informasi Kendali Kelengkapan SPJ Digital Berbasis Laravel

## 1. Tujuan Pembuatan

Buat aplikasi web bernama SI-KANCIL menggunakan Laravel.

Aplikasi ini digunakan oleh Sekretaris Tim untuk membantu mengontrol proses administrasi SPJ.

Aplikasi ini tidak menggantikan sistem BOS, PortalGO, SAKTI, ETTD, maupun SRIKANDI.

Sistem hanya digunakan untuk:
- mencatat data permintaan/FPA
- memonitor kelengkapan dokumen SPJ
- mengetahui posisi proses SPJ
- menyimpan riwayat perubahan
- mengatur jadwal kegiatan
- menyimpan template dokumen

Project Laravel sudah tersedia.
Composer install sudah berhasil dilakukan.

Gunakan project yang sudah ada.


---

# 2. Konsep Utama Sistem

Alur bisnis:

BOS membuat FPA
        |
        |
        v
Sekretaris mencatat data FPA di SI-KANCIL
        |
        |
        v
Sistem membuat checklist SPJ sesuai jenis pengeluaran
        |
        |
        v
Petugas mengumpulkan dokumen
        |
        |
        v
Sekretaris mengirim SPJ ke PPK
        |
        |
        +----------------+
        |                |
        v                v
   Perbaikan          Selesai


---

# 3. User Sistem

Untuk tahap MVP hanya ada 1 role:

## Sekretaris Tim

User dapat:

- login
- melihat dashboard
- membuat permintaan/FPA
- mengubah checklist dokumen
- upload file
- mengubah status SPJ
- melihat history
- menggunakan kalender
- mengelola template dokumen


---

# 4. Modul Authentication

Buat fitur:

- Login
- Logout
- Middleware auth

Tidak perlu membuat role kompleks.

Default user:

Sekretaris


---

# 5. Modul Permintaan/FPA

## Penjelasan

Data utama sistem adalah data permintaan/FPA.

Nomor FPA berasal dari BOS.

SI-KANCIL tidak membuat nomor FPA.

User hanya memasukkan data FPA secara manual.


## Contoh:

Nomor FPA:

FP-2026-667498-92800-713


Deskripsi:

Honor Petugas Pendataan Survei Sakernas Agustus 2026


---

## Database: requests

Buat tabel:

requests

Field:

id
nomor_fpa
deskripsi_permintaan
jenis_pengeluaran_id
periode
tanggal_mulai
tanggal_selesai
lokasi
deadline_spj
status_spj
created_at
updated_at


---

## Fitur:

CRUD:

- tambah permintaan
- edit permintaan
- lihat detail
- hapus jika belum diproses


Search:

User dapat mencari berdasarkan:

- nomor FPA
- deskripsi permintaan
- periode
- status


---

# 6. Master Jenis Pengeluaran

Buat master jenis pengeluaran.

Jenis pengeluaran menentukan checklist dokumen.


Database:

expense_types


Contoh data:


1. HONOR

2. TRANSLOK

3. TRANSLOK DAERAH SULIT

4. PERJALANAN DINAS

5. MEETING KANTOR

6. MEETING LUAR KANTOR



---

# 7. Template Checklist SPJ


Buat sistem template checklist.

Ketika user memilih jenis pengeluaran:

Sistem otomatis membuat checklist dokumen.


Database:


document_templates


Field:

id
expense_type_id
nama_dokumen
is_required



---

# Checklist berdasarkan jenis pengeluaran


## HONOR

Dokumen:

- FPA
- KAK
- Kuitansi BOS


---

## TRANSLOK

Dokumen:

- FPA
- KAK
- Surat Tugas
- Laporan Perjalanan
- Dokumentasi
- Visum
- Pengeluaran Riil + Surat Non Kendaraan Dinas


---

## TRANSLOK DAERAH SULIT

Dokumen:

- FPA
- KAK
- Surat Tugas
- Laporan Perjalanan
- Dokumentasi
- Visum
- Pengeluaran Riil + Surat Non Kendaraan Dinas


---

## PERJALANAN DINAS

Dokumen:

- FPA
- KAK
- Surat Tugas
- SPD/SPPD
- Laporan Perjalanan
- Dokumentasi
- Visum
- Pengeluaran Riil + Surat Non Kendaraan Dinas


---

## MEETING KANTOR

Dokumen:

- FPA
- KAK
- Undangan Kegiatan
- Jadwal Kegiatan
- Surat Tugas
- Notulensi
- Daftar Hadir
- Nota Konsumsi
- Dokumentasi
- Daftar Penerima Perlengkapan
- Surat Non Kendaraan Dinas + Pengeluaran Riil


---

## MEETING LUAR KANTOR

Dokumen:

- FPA
- KAK
- Undangan Kegiatan
- Jadwal Kegiatan
- Surat Tugas
- Notulensi
- Daftar Hadir
- Nota Konsumsi
- Dokumentasi
- Daftar Penerima Perlengkapan
- Surat Non Kendaraan Dinas + Pengeluaran Riil


---

# 8. Checklist Dokumen


Setiap FPA memiliki checklist.


Database:

spj_checklists


Field:

id
request_id
nama_dokumen
status
catatan
file_path
created_at
updated_at



Status dokumen:

- Belum Ada
- Belum Lengkap
- Lengkap
- Perlu Perbaikan


Jangan gunakan checkbox true/false.


---

# 9. Kanban Checklist Dokumen


Pada halaman detail FPA buat tampilan Kanban.


Card:

Dokumen SPJ


Kolom:


BELUM ADA

BELUM LENGKAP

LENGKAP

PERLU PERBAIKAN


User dapat drag card antar kolom.


Saat pindah status:

- update database
- simpan history


---

# 10. History Checklist


Semua perubahan checklist harus dicatat.


Database:

checklist_histories


Field:

id
checklist_id
status_lama
status_baru
catatan
user_id
created_at



Contoh:

Tanggal:
20 Agustus 2026

Dari:
Belum Lengkap

Ke:
Lengkap

Catatan:
Dokumen sudah diperbaiki


---

# 11. Workflow Status SPJ


Status utama:


Persiapan

↓

Pelaksanaan

↓

Pengumpulan SPJ

↓

Dikirim ke PPK

↓

Perbaikan

↓

Selesai



Database:

request_status_histories


Field:

id
request_id
status_lama
status_baru
catatan
user_id
created_at



---

# Aturan Status


## Dikirim ke PPK

Ketika sekretaris mengirim SPJ.

Simpan:

- tanggal kirim
- user


---

## Perbaikan

Digunakan jika ada koreksi dari:

- PPK
- Bendahara
- pemeriksa


Tidak perlu membedakan pihak.


Tambahkan:

- catatan
- file bukti optional


Contoh:

"Nota transport belum sesuai"


---

## Selesai

Sekretaris melakukan pengecekan manual.

Referensi:

- Realisasi BOS
- Bukti CMS Bendahara


Tambahkan:

- tanggal selesai
- catatan
- upload bukti optional



---

# 12. Kanban Monitoring FPA


Dashboard memiliki Kanban kedua.


Fungsi:

Melihat posisi semua FPA.


Card:

1 FPA


Kolom:


Persiapan

Pelaksanaan

Pengumpulan SPJ

Dikirim ke PPK

Perbaikan

Selesai



---

## Filter Kanban

Wajib ada:

- bulan
- tahun
- pencarian


Default:

bulan berjalan


Jangan tampilkan seluruh FPA satu tahun sekaligus.


---

# 13. Detail Surat Tugas


Surat Tugas tidak hanya upload file.


Buat form:


nomor_surat_tugas

tanggal_surat_tugas

pelaksana

isi_tugas


Database:

surat_tugas_details


---

# 14. Detail SPD/SPPD


Buat form:


nomor_spd

nama_pelaksana

maksud_perjalanan

tempat_berangkat

tempat_tujuan

tanggal_berangkat

tanggal_kembali

transportasi



Database:

travel_details



---

# 15. Detail Pengeluaran Riil + Surat Non Kendaraan Dinas


Jadikan satu form.


Field:


nomor_surat_tugas

tanggal_surat_tugas

nama_pelaksana

jabatan

tanggal_kegiatan

uraian_pengeluaran

jumlah_pengeluaran

keterangan


Database:

real_expense_details



---

# 16. Laporan Perjalanan


Buat form:


nama_pelaksana

tujuan

uraian_kegiatan

tanggal_kegiatan

dokumentasi


Database:

travel_reports



---

# 17. Upload File


Gunakan Laravel Storage.


File yang bisa upload:

- KAK
- FPA
- Surat Tugas
- SPD/SPPD
- Laporan Perjalanan
- Dokumentasi
- Bukti Perbaikan
- Bukti CMS


---

# 18. Kalender


Gunakan FullCalendar.


Fitur:


## Drag Select

User dapat memilih tanggal.


Contoh:


25 Agustus 2026
-
29 Agustus 2026



Simpan:


tanggal_mulai

tanggal_selesai



---

## Input Form


Gunakan date range picker.


Contoh:


25/08/2026 - 29/08/2026



---

## Event Kalender


Tampilkan:


- deskripsi permintaan
- nomor FPA
- lokasi
- status SPJ
- deadline


---

# 19. Repository Template


Buat menu:


Repository Template


Kategori:


- KAK
- Surat Tugas
- Laporan Perjalanan
- Visum
- Superkendis
- Dokumen SPJ



Database:


templates


Field:


id

nama_template

kategori

versi

file

status_aktif



---

# 20. Dashboard


Tampilkan:


Card:


Total FPA

Pengumpulan SPJ

Dikirim ke PPK

Perbaikan

Selesai



Tabel:


- nomor FPA
- deskripsi
- deadline
- status
- progress checklist



---

# 21. Urutan Pengerjaan


Kerjakan bertahap:


## Sprint 1

Setup:

- cek Laravel
- authentication
- database migration


## Sprint 2

Modul:

- jenis pengeluaran
- permintaan/FPA


## Sprint 3

Modul:

- checklist SPJ
- template checklist


## Sprint 4

Modul:

- Kanban checklist
- history checklist


## Sprint 5

Modul:

- workflow status SPJ
- history status


## Sprint 6

Modul:

- detail dokumen
- upload file


## Sprint 7

Modul:

- kalender
- repository template


## Sprint 8

Testing dan perbaikan UI



---

# Acceptance Criteria


Aplikasi selesai jika:


[x] User dapat login

[x] User dapat membuat data FPA

[x] User dapat memilih jenis pengeluaran

[x] Checklist otomatis dibuat berdasarkan jenis pengeluaran

[x] Checklist menggunakan status

[x] Checklist memiliki history

[x] FPA memiliki workflow status

[x] Status memiliki history

[x] Terdapat dua Kanban:
- Kanban FPA bulanan
- Kanban checklist dokumen


[x] Kalender dapat drag select

[x] Kalender dapat menggunakan date range picker

[x] Dokumen memiliki metadata tambahan

[x] File dapat diupload

[x] Repository template tersedia



---

# Catatan Implementasi

Prioritaskan fungsi utama terlebih dahulu.

Jangan membuat:

- integrasi BOS
- integrasi SAKTI
- integrasi PortalGO
- integrasi CMS


Semua input dilakukan manual pada tahap MVP.


Gunakan kode Laravel yang sederhana, mudah dipahami, dan mengikuti standar MVC.

