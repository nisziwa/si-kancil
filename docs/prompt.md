# prompt 31 Agustus 2026 05.00 WITA 
```{txt}
SI-KANCIL Business Process Update — Status SPJ, Validasi Workflow, Surat Tugas & Superkendis

Mode:
BUILD / IMPLEMENTATION


==================================================
PROJECT STATE IMPORTANT
==================================================

Gunakan repository saat ini sebagai satu-satunya sumber kebenaran.

Catatan penting:

Project pernah mengalami percobaan migrasi Livewire, tetapi perubahan tersebut sudah di-revert.

Jangan menggunakan asumsi dari percobaan Livewire sebelumnya.

Jangan melakukan:
- migrasi ke Livewire
- redesign UI
- perubahan architecture besar

Ikuti architecture yang benar-benar ada di repository saat ini.


Sebelum melakukan coding:

Review:

- model existing
- migration existing
- controller existing
- route existing
- blade/view existing
- database relationship
- history yang sudah berjalan


Jangan membuat ulang fitur yang sudah ada.


==================================================
TAHAP 0 — SYNC REPOSITORY
==================================================

Sebelum melakukan perubahan:


1. Cek status:

git status


2. Pull repository terbaru:

git pull


Jika terdapat perubahan lokal:

- review terlebih dahulu
- jangan menghapus perubahan existing


==================================================
TAHAP 1 — REVIEW DOKUMENTASI
==================================================

Baca:


docs/IMPLEMENTATION_PLAN.md

docs/TASK_PROGRESS.md

docs/HANDOFF.md


Gunakan dokumentasi sebagai referensi.

Namun jika dokumentasi berbeda dengan kondisi kode:

gunakan kondisi repository sebagai sumber kebenaran.


==================================================
TAHAP 2 — IMPLEMENTASI PERUBAHAN
==================================================


# 1. PERUBAHAN STATUS SPJ


Status SPJ FINAL:


1. Persiapan

2. Dikirim ke PPK

3. Selesai

4. Perbaikan


Hapus status lama yang sudah tidak digunakan.


Sesuaikan:

- tampilan status
- tombol perubahan status
- Kanban jika ada
- history status


History tetap mencatat:

- status sebelumnya
- status baru
- user yang mengubah
- waktu perubahan



==================================================
# 2. ATURAN PERUBAHAN STATUS SPJ
==================================================


Perubahan status SPJ tidak boleh dilakukan secara bebas.


Contoh:


Persiapan

↓

Dikirim ke PPK


Sebelum perubahan status dilakukan, sistem harus mengecek:


- Nomor FPA sudah tersedia
- Checklist dokumen wajib sudah berstatus "Lengkap"
- Dokumen wajib sudah tersedia


Jika belum terpenuhi:

Status tidak boleh berubah.


Tampilkan popup/peringatan:


"SPJ belum dapat dikirim ke PPK.

Silakan lengkapi checklist dokumen terlebih dahulu."


==================================================
# 3. ALUR STATUS TIDAK WAJIB MELEWATI PERBAIKAN
==================================================


Status Perbaikan bukan tahapan wajib.


Alur yang diperbolehkan:


Persiapan

↓

Dikirim ke PPK

↓

Selesai



atau:



Persiapan

↓

Dikirim ke PPK

↓

Perbaikan

↓

Selesai



Status Perbaikan digunakan apabila ada dokumen atau proses yang perlu diperbaiki.


==================================================
# 4. PROGRESS SPJ
==================================================


Progress SPJ bukan digunakan sebagai checklist kelengkapan dokumen.


Gunakan untuk menunjukkan prioritas SPJ.


Tampilkan informasi:


- deadline
- sisa hari
- keterlambatan


Contoh:


Sisa 3 hari:

Prioritas normal


Melewati deadline:

Prioritas tinggi / warning


Tujuan:

Membantu sekretaris mengetahui SPJ yang harus segera diproses.


==================================================
# 5. VALIDASI NOMOR FPA
==================================================


Nomor FPA tidak boleh duplikat.


Saat user mengetik nomor FPA:

Lakukan pengecekan langsung.


Jika nomor sudah digunakan:


Tampilkan warning langsung di bawah field:


"Nomor FPA sudah digunakan."


Tidak perlu menunggu submit form.


==================================================
# 6. PERUBAHAN KALENDER
==================================================


Saat memilih tanggal melalui kalender:


Jangan gunakan alert JavaScript terlebih dahulu.


Alur baru:


Klik tanggal kalender

↓

langsung tampil form FPA


Tanggal otomatis terisi.


Deadline otomatis:


Tanggal akhir kegiatan + 3 hari


Namun:

- field deadline tetap aktif
- user dapat mengubah manual


==================================================
# 7. DUKUNGAN FPA TANPA NOMOR FPA
==================================================


Pada kondisi tertentu:

Surat Tugas dapat dibuat terlebih dahulu sebelum FPA.


Contoh:

- translok
- kegiatan non perjalanan dinas


Sistem harus mendukung kondisi:

FPA belum memiliki nomor.


Jika nomor FPA kosong:


Tampilkan keterangan:


"Belum ada nomor FPA"


Gunakan tampilan abu-abu.


Namun ketika:


Persiapan

↓

Dikirim ke PPK


Nomor FPA wajib tersedia.


==================================================
# 8. TEMPLATE CHECKLIST SURAT TUGAS
==================================================


Untuk kegiatan yang membutuhkan Surat Tugas:


Tambahkan checklist:


SURAT TUGAS


Checklist tetap menggunakan status:


- Lengkap
- Belum Lengkap


==================================================
# 9. PERUBAHAN FORM FPA
==================================================


Hilangkan field:


- Periode Kegiatan
- Lokasi


Ganti periode kegiatan menjadi pilihan:


- Bulanan
- Triwulanan
- Subround
- Semester
- Tahunan


Gunakan tombol pilihan.


==================================================
# 10. DETAIL SURAT TUGAS
==================================================


Pada detail FPA tambahkan informasi:


- Nomor Surat Tugas
- Tanggal Surat Tugas
- Pelaksana Surat Tugas


Untuk banyak pelaksana:


Jangan menggunakan satu text field panjang.


Gunakan tabel/list.


Contoh:


No | Nama Pelaksana | Nomor Surat

1 | Nama A | B-1027.1/75040/KP.650/2026

2 | Nama B | B-1027.2/75040/KP.650/2026



Nomor sub dibuat otomatis.


Contoh:


Nomor utama:


B-1027/75040/KP.650/2026


Menjadi:


B-1027.1/75040/KP.650/2026

B-1027.2/75040/KP.650/2026

B-1027.3/75040/KP.650/2026



Checklist Surat Tugas menjadi:


Lengkap


Jika:

- nomor surat tugas tersedia
- tanggal surat tugas tersedia
- seluruh pelaksana tersedia


==================================================
# 11. FITUR SUPERKENDIS
==================================================


Tambahkan fitur generate Superkendis.


Lokasi:

Detail FPA


Tambahkan tombol:


Generate Superkendis


Syarat:

Checklist Surat Tugas harus:


Lengkap


==================================================
# 12. DATABASE SK RATE PERJALANAN
==================================================


Tambahkan tabel baru:


sk_rate_perjalanan


Berisi:


- kecamatan
- besaran biaya transport
- keterangan


Sesuaikan dengan tabel rate perjalanan yang tersedia.


==================================================
# 13. FORM SUPERKENDIS
==================================================


Sebelum generate:


Isi:


- Nama pelaksana
- NIP
- Kecamatan tujuan
- Tanggal perjalanan


Tanggal:

Gunakan tanggal tunggal.


==================================================
# 14. VALIDASI EXPORT SUPERKENDIS
==================================================


Export hanya dapat dilakukan jika:


Wajib:

- nama pelaksana
- tempat tujuan
- tanggal perjalanan


NIP:


Tidak wajib.


Jika kosong:


Isi:


-


Jika format NIP tidak sesuai:


Isi:


-


==================================================
# 15. OUTPUT SUPERKENDIS
==================================================


Generate:


- DOCX
- PDF


Nama file:


Superkendis_NamaPelaksana


Contoh:


Superkendis_Budi.docx


==================================================
# 16. BULK DOWNLOAD SUPERKENDIS
==================================================


Tambahkan fitur bulk download.


Pilihan:


1. Pisah file


Contoh:


Superkendis_A.docx

Superkendis_B.docx



2. Gabung menjadi satu file


Contoh:


Superkendis_Gabungan.docx


Validasi tetap berlaku:

- tujuan harus ada
- tanggal harus ada


==================================================
TAHAP 3 — UPDATE DOKUMENTASI
==================================================


Setelah implementasi selesai:


WAJIB update:


docs/IMPLEMENTATION_PLAN.md


Tambahkan perubahan sebagai versi baru.


docs/TASK_PROGRESS.md


Update:

- status pekerjaan
- tanggal selesai
- ringkasan perubahan
- commit hash


docs/HANDOFF.md


Update:

- Current Sprint
- Current Status
- Completed
- Remaining Tasks
- Important Decisions
- Last Commit


Dokumentasi harus mencerminkan kondisi repository terbaru.


==================================================
TAHAP 4 — TESTING
==================================================


Jalankan:


php artisan test


Jika project memiliki frontend build:


npm run build


Perbaiki error jika ditemukan.


==================================================
TAHAP 5 — GIT COMMIT
==================================================


Sebelum commit:


git status


Review semua perubahan.


Commit:


feat(spj): update SPJ workflow validation and document management


Kemudian:


git push


==================================================
TAHAP 6 — FINAL REPORT
==================================================


Setelah selesai tampilkan:


- ringkasan perubahan
- file yang berubah
- hasil testing
- commit hash


Kemudian berhenti.


Jangan mengerjakan fitur lain tanpa instruksi user.
```
