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
Status: In Progress
Tanggal mulai: 2026-08-30
Ringkasan sementara:
- Membuat RequestStatusController
- Membuat komponen status workflow
- Menambahkan route perubahan status SPJ
- Integrasi workflow pada detail FPA
- Testing dasar berhasil

## Sprint 6 — Detail Dokumen & Upload File
Status: Pending

## Sprint 7 — Dashboard, Kanban FPA, Kalender & Template
Status: Pending

## Sprint 8 — Layout, UI Polish & Testing
Status: Pending
