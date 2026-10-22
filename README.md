# 📋 Sistem Aplikasi Survei & Kuesioner Online

Aplikasi kuesioner dan survei online modern berbasis web dengan arsitektur terpisah antara **Backend REST API (Laravel + Sanctum Stateful Auth)** dan **Frontend SPA (Vue 3 + TypeScript + Vite + Tailwind CSS + Pinia + Chart.js)**.

---

## 🌟 Fitur Utama Sistem

### 1. Manajemen Akun & Hak Akses (User Management)
Sistem memiliki 2 tingkatan peran (*role*): **Administrator** dan **Pengguna Biasa (User)**.

- **Atribut Data Pengguna**:
  - `id`: ID pengguna unik numerik (auto-increment)
  - `name`: Nama lengkap
  - `email`: Alamat surel unik (`unique`)
  - `birth_date`: Tanggal lahir (`date`)
  - `phone`: Nomor telepon / HP (`varchar(20)`)
  - `role`: Peran akun (`admin` / `user`)
  - `approval_status`: Status persetujuan akun (`pending` / `approved` / `rejected`)
  - `is_active`: Status keaktifan akun (`true` / `false`)

- **Alur Pendaftaran & Persetujuan Akun (Approval Workflow)**:
  - **Pendaftaran Publik**: Pengguna mendaftar secara mandiri melalui form registrasi lengkap (Nama, Email, Password, Tanggal Lahir, No. HP).
  - Akun yang baru mendaftar berstatus `pending` dan `is_active: false`. Pengguna belum dapat masuk ke sistem sebelum disetujui.
  - **Verifikasi oleh Administrator**:
    - Administrator dapat melihat daftar pendaftar di tab / filter **Menunggu Persetujuan (Pending)**.
    - Admin dapat menekan tombol **Setujui (ACC / Approve)** untuk mengaktifkan akun pendaftar.
    - Admin dapat menolak pendaftaran (**Reject**) jika data tidak valid.

- **Fitur Khusus Administrator**:
  - Statistik pengguna realtime: Total Pengguna, Jumlah Admin, Jumlah User Reguler, Menunggu Persetujuan, dan Akun Aktif.
  - Pencarian dan filter pengguna berdasarkan peran (*role*), status persetujuan (*approval status*), serta kata kunci (nama, email, atau nomor HP).
  - **Tambah Pengguna Baru**: Admin dapat menambahkan akun admin baru atau pengguna biasa secara langsung (otomatis berstatus *approved*).
  - **Edit Pengguna**: Mengubah nama, surel, peran (*role*), status keaktifan, maupun reset kata sandi.
  - **Nonaktifkan Akun (Disable User)**: Toggle status aktif/non-aktif. Jika akun dinonaktifkan, seluruh sesi login dan token akses pengguna langsung dicabut secara otomatis (*session revocation*).
  - **Hapus Akun**: Dilengkapi proteksi keamanan untuk mencegah admin menghapus akunnya sendiri yang sedang aktif dan mencegah penghapusan admin terakhir di sistem.

- **Fitur Khusus Pengguna Biasa**:
  - Login dan logout aman berbasis *cookie session*.
  - **Halaman Profil Mandiri**: Pengguna dapat melihat data pribadi serta memperbarui nama lengkap, email, tanggal lahir, dan nomor HP secara mandiri.

---

### 2. Pengelolaan Kuesioner / Survei (Admin)
- **Struktur Kategori & Pertanyaan**:
  - Setiap survei memiliki **Judul** dan **Deskripsi**.
  - Survei dikelompokkan ke dalam **beberapa kategori** (*multi-category*).
  - Setiap kategori memuat sejumlah pertanyaan dengan pilihan ganda berlabel **A, B, C, dan D**.
- **Masa Tayang & Siklus Hidup Survei**:
  - Dilengkapi pengaturan tanggal awal tayang (`publish_start_at`) dan akhir tayang (`publish_end_at`).
  - Status survei: **Draft** vs **Published**.
  - Survei berstatus **Draft** dapat ditinjau, diedit struktur kategorinya, atau dihapus.
  - Survei yang telah dipublikasikan (**Published**) dikunci dari pengeditan struktur untuk menjaga integritas data jawaban responden.
- **Visualisasi Hasil & Laporan Responden**:
  - **Tabel Ringkasan Jawaban**: Menampilkan rekapitulasi jumlah pemilih dan persentase untuk setiap opsi (A, B, C, D) lengkap dengan indikator bilah progres (*progress bar*).
  - **Grafik Interaktif (Chart.js)**: Menyajikan grafik visual **Bar Chart** dan **Doughnut / Pie Chart** untuk setiap butir pertanyaan secara dinamis.
  - **Daftar Responden**: Menampilkan daftar peserta yang telah berpartisipasi beserta tanggal pengisian.
  - **Modal Detail Jawaban**: Admin dapat mengklik salah satu responden untuk melihat rincian opsi jawaban spesifik yang dipilih oleh responden tersebut.

---

### 3. Pengisian & Riwayat Partisipasi Survei (User)
- **Katalog Survei Tersedia**:
  - Menampilkan daftar survei berstatus *published* yang sedang aktif dan berada dalam rentang masa tayang.
  - Proteksi otomatis: Survei yang belum mulai atau sudah lewat masa tayang tidak dapat diakses.
- **Proteksi Pengisian Tunggal**:
  - Setiap pengguna hanya dapat mengisi suatu survei **tepat 1 (satu) kali**.
  - Survei yang telah diselesaikan akan ditandai dengan lencana (**Badge**) *"Sudah Diisi"* dan tombol partisipasi dinonaktifkan.
- **Antarmuka Pengisian Responsif**:
  - Pertanyaan tersusun rapi berdasarkan kategori.
  - Indikator kemajuan (*progress bar*) kelengkapan pengisian secara *realtime*.
  - Validasi wajib menjawab seluruh pertanyaan sebelum formulir dapat dikirim (*submit*).
- **Riwayat Partisipasi**:
  - Pengguna dapat melihat daftar seluruh kuesioner yang pernah diikutinya beserta waktu penyelesaian.
  - Dilengkapi tombol **Lihat Jawaban Saya** untuk meninjau kembali jawaban yang telah dikirimkan sebelumnya.

---

## Akun Uji Coba Bawaan (Default Seeded Accounts)

Setelah menjalankan database seeder (`php artisan migrate:fresh --seed`), akun-akun berikut siap digunakan:

| Peran (*Role*) | Alamat Email | Kata Sandi | Status Akun | Deskripsi & Fungsi Uji Coba |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin@survey.com` | `password1234` | Approved / Aktif | Akses penuh ke seluruh menu Admin, CRUD User/Survei, ACC Pendaftaran, dan Visualisasi Grafik |
| **User Biasa** | `budi@survey.com` | `password1234` | Approved / Aktif | Pengguna aktif yang sudah berpartisipasi mengisi survei contoh |
| **User Biasa** | `siti@survey.com` | `password1234` | Approved / Aktif | Pengguna aktif yang sudah berpartisipasi mengisi survei contoh |
| **User Biasa** | `andi@survey.com` | `password1234` | Approved / Non-Aktif | Contoh akun yang diblokir/dinonaktifkan (untuk menguji validasi login user non-aktif) |
| **User Biasa** | `rina@survey.com` | `password1234` | Pending / Non-Aktif | Contoh akun pendaftar baru yang menunggu persetujuan (untuk menguji tombol ACC Admin) |

> [!NOTE]
> Kata sandi untuk seluruh akun seeder adalah: **`password1234`** *(minimal 12 karakter sesuai standar keamanan aplikasi)*.

---

## 💻 Spesifikasi Teknologi (Tech Stack)

### Backend
- **Framework**: Laravel 12 / 13 (PHP ^8.3)
- **Otentikasi**: Laravel Sanctum (Stateful Cookie & Session Based SPA Authentication)
- **Database**: MySQL / MariaDB (Didukung penuh oleh Eloquent ORM & Migrations)
- **Proteksi**: Form Request Validation, CSRF Protection, Active User Middleware, Throttle Rate Limiting

### Frontend
- **Framework**: Vue 3 (Composition API dengan `<script setup>`)
- **Bahasa**: TypeScript (^6.0)
- **Build Tool**: Vite 8
- **State Management**: Pinia 4
- **Routing**: Vue Router 4 (dilengkapi Navigation Guards berbasis *auth* dan *role*)
- **Styling**: Tailwind CSS v4 + Radix Vue + Lucide Icons
- **Visualisasi Data**: Chart.js & vue-chartjs (Bar & Doughnut Charts)

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

Pastikan di lingkungan komputer Anda telah terpasang:
- **PHP** >= 8.3
- **Composer** (v2.x)
- **Node.js** (v20+ atau v22+) & **npm**
- **MySQL** / MariaDB (via XAMPP, Laragon, Docker, atau MySQL Server lokal)

---

### Langkah 1: Pengaturan Backend (Laravel)

1. Buka terminal dan masuk ke direktori backend:
   ```bash
   cd backend
   ```

2. Pasang dependensi PHP melalui Composer:
   ```bash
   composer install
   ```

3. Salin berkas lingkungan (*environment file*):
   ```bash
   cp .env.example .env
   ```
   *(Pada Windows PowerShell / Command Prompt dapat menggunakan: `copy .env.example .env`)*

4. Buat kunci enkripsi aplikasi:
   ```bash
   php artisan key:generate
   ```

5. Buat basis data MySQL bernama `survey` (misalnya via phpMyAdmin, DBeaver, atau MySQL CLI):
   ```sql
   CREATE DATABASE survey CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

6. Jalankan migrasi basis data dan masukkan data awal (*seeder*):
   ```bash
   php artisan migrate:fresh --seed
   ```

7. Jalankan server backend:
   ```bash
   php artisan serve
   ```
   Backend API akan aktif dan melayani permintaan di: **`http://localhost:8000`**

---

### Langkah 2: Pengaturan Frontend (Vue.js + Vite)

1. Buka jendela terminal baru, lalu masuk ke direktori frontend:
   ```bash
   cd frontend
   ```

2. Pasang dependensi paket Node.js:
   ```bash
   npm install
   ```

3. Pastikan konfigurasi berkas `frontend/.env` telah mengarah ke server backend

4. Jalankan server pengembang (*development server*):
   ```bash
   npm run dev
   ```
   Aplikasi web frontend akan aktif di: **`http://localhost:5173`**

5. Buka peramban (*browser*) Anda dan akses:
   👉 **`http://localhost:5173`**

---

## 📑 Daftar Endpoint REST API

Semua respons disajikan dalam format JSON standar (`application/json`).

### 1. Otentikasi & Sesi
| Metode | Endpoint | Middleware | Keterangan |
| :--- | :--- | :--- | :--- |
| `GET` | `/sanctum/csrf-cookie` | Public | Inisialisasi token proteksi CSRF Sanctum |
| `POST` | `/api/register` | `throttle:register` | Registrasi akun pengguna baru (status otomatis `pending`) |
| `POST` | `/api/login` | `throttle:login` | Masuk ke akun (validasi status aktif & disetujui) |
| `POST` | `/api/logout` | `auth:sanctum` | Keluar dan menghancurkan sesi aktif |
| `GET` | `/api/me` | `auth:sanctum` | Mengambil data pengguna yang sedang login |

### 2. Profil Pengguna
| Metode | Endpoint | Middleware | Keterangan |
| :--- | :--- | :--- | :--- |
| `PUT` | `/api/profile` | `auth:sanctum`, `active_user` | Memperbarui nama, email, tgl lahir, & nomor HP sendiri |

### 3. Partisipasi Survei (User Biasa)
| Metode | Endpoint | Middleware | Keterangan |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/surveys/available` | `auth:sanctum`, `active_user` | Mengambil daftar kuesioner yang aktif dalam masa tayang |
| `GET` | `/api/surveys/{survey}/fill` | `auth:sanctum`, `active_user` | Mengambil struktur kategori, soal, dan opsi untuk pengisian |
| `POST` | `/api/surveys/{survey}/submit` | `auth:sanctum`, `active_user` | Mengirimkan lembar jawaban kuesioner (1x per user) |
| `GET` | `/api/surveys/my-history` | `auth:sanctum`, `active_user` | Melihat riwayat survei yang pernah diikuti beserta jawabannya |

### 4. Manajemen Pengguna (Administrator)
| Metode | Endpoint | Middleware | Keterangan |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/admin/users` | `admin` | Daftar seluruh akun (filter role, status, pencarian, & statistik) |
| `POST` | `/api/admin/users` | `admin` | Membuat akun admin atau user baru secara langsung |
| `GET` | `/api/admin/users/{user}` | `admin` | Melihat rincian profil pengguna tertentu |
| `PUT` | `/api/admin/users/{user}` | `admin` | Memperbarui data pengguna, peran, status, atau password |
| `DELETE` | `/api/admin/users/{user}` | `admin` | Menghapus akun (dengan proteksi self-delete & last admin) |
| `PATCH` | `/api/admin/users/{user}/approve` | `admin` | Menyetujui pendaftaran akun pengguna (ACC) |
| `PATCH` | `/api/admin/users/{user}/reject` | `admin` | Menolak pendaftaran akun pengguna |
| `PATCH` | `/api/admin/users/{user}/toggle-status`| `admin` | Mengaktifkan / menonaktifkan status akun & mencabut sesi |

### 5. Pengelolaan Survei & Hasil (Administrator)
| Metode | Endpoint | Middleware | Keterangan |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/admin/surveys` | `admin` | Melihat seluruh survei (draft, published, expired) |
| `POST` | `/api/admin/surveys` | `admin` | Membuat survei baru dengan kategori dan opsi soal (A-D) |
| `GET` | `/api/admin/surveys/{survey}` | `admin` | Melihat detail survei dan butir pertanyaannya |
| `PUT` | `/api/admin/surveys/{survey}` | `admin` | Memperbarui kuesioner (khusus survei berstatus draft) |
| `PATCH` | `/api/admin/surveys/{survey}/publish`| `admin` | Mempublikasikan kuesioner ke publik |
| `DELETE` | `/api/admin/surveys/{survey}` | `admin` | Menghapus kuesioner (khusus status draft) |
| `GET` | `/api/admin/surveys/{survey}/results` | `admin` | Rekapitulasi hasil pilihan jawaban dan persentase |
| `GET` | `/api/admin/surveys/{survey}/respondents`| `admin` | Daftar responden dan rincian pilihan jawaban masing-masing |

---

## 🗄️ Skema Basis Data Relasional

```
+-------------------------------------------------------+
|                         users                         |
+-------------------------------------------------------+
| id               : BIGINT UNSIGNED (PK)               |
| name             : VARCHAR(255)                       |
| email            : VARCHAR(255) (UNIQUE)              |
| password         : VARCHAR(255)                       |
| birth_date       : DATE (NULLABLE)                    |
| phone            : VARCHAR(20) (NULLABLE)             |
| role             : ENUM('admin', 'user')              |
| approval_status  : ENUM('pending','approved','rejected')|
| is_active        : BOOLEAN (DEFAULT TRUE)             |
| timestamps       : created_at, updated_at             |
+-------------------------------------------------------+
        | 1
        | hasMany
        v *
+-------------------------------------------------------+
|                        survey                         |
+-------------------------------------------------------+
| id               : BIGINT UNSIGNED (PK)               |
| created_by       : BIGINT UNSIGNED (FK -> users.id)   |
| title            : VARCHAR(255)                       |
| description      : TEXT (NULLABLE)                    |
| status           : ENUM('draft', 'published')         |
| publish_start_at : DATETIME (NULLABLE)                |
| publish_end_at   : DATETIME (NULLABLE)                |
| published_at     : TIMESTAMP (NULLABLE)               |
| timestamps       : created_at, updated_at             |
+-------------------------------------------------------+
        | 1
        | hasMany
        v *
+-------------------------------------------------------+
|                   survey_categories                   |
+-------------------------------------------------------+
| id               : BIGINT UNSIGNED (PK)               |
| survey_id        : BIGINT UNSIGNED (FK -> survey.id)  |
| name             : VARCHAR(255)                       |
| description      : TEXT (NULLABLE)                    |
| sort_order       : INT (DEFAULT 0)                    |
+-------------------------------------------------------+
        | 1
        | hasMany
        v *
+-------------------------------------------------------+
|                       questions                       |
+-------------------------------------------------------+
| id                 : BIGINT UNSIGNED (PK)             |
| survey_category_id : BIGINT UNSIGNED (FK)             |
| question_text      : TEXT                             |
| sort_order         : INT (DEFAULT 0)                  |
+-------------------------------------------------------+
        | 1
        | hasMany
        v *
+-------------------------------------------------------+
|                   question_options                    |
+-------------------------------------------------------+
| id            : BIGINT UNSIGNED (PK)                  |
| question_id   : BIGINT UNSIGNED (FK -> questions.id)  |
| label         : VARCHAR(10) (e.g. 'A', 'B', 'C', 'D') |
| option_text   : TEXT                                  |
+-------------------------------------------------------+

+-------------------------------------------------------+
|                   survey_responses                    |
+-------------------------------------------------------+
| id         : BIGINT UNSIGNED (PK)                     |
| survey_id  : BIGINT UNSIGNED (FK -> survey.id)        |
| user_id    : BIGINT UNSIGNED (FK -> users.id)         |
| timestamps : created_at, updated_at                   |
| UNIQUE     : (survey_id, user_id)                     |
+-------------------------------------------------------+
        | 1
        | hasMany
        v *
+-------------------------------------------------------+
|                    response_answers                   |
+-------------------------------------------------------+
| id                 : BIGINT UNSIGNED (PK)             |
| survey_response_id : BIGINT UNSIGNED (FK)             |
| question_id        : BIGINT UNSIGNED (FK)             |
| question_option_id : BIGINT UNSIGNED (FK)             |
+-------------------------------------------------------+
```

---

## 📂 Struktur Direktori Proyek

```
tesKompetensi/
├── README.md                                 # Dokumentasi utama proyek
├── backend/                                  # Aplikasi Backend Laravel REST API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php            # Register, Login, Logout, Info Me
│   │   │   │   ├── UserController.php            # Profil, User Mgmt, ACC/Reject, Toggle Status
│   │   │   │   ├── SurveyController.php          # CRUD & Publikasi Survei
│   │   │   │   ├── SurveyResponseController.php  # Pengisian kuesioner & riwayat user
│   │   │   │   └── SurveyResultController.php    # Rekap hasil survei & daftar responden
│   │   │   ├── Middleware/
│   │   │   │   ├── AdminMiddleware.php           # Verifikasi akses khusus role admin
│   │   │   │   └── ActiveUserMiddleware.php      # Blokir akses jika user dinonaktifkan
│   │   │   ├── Requests/                         # Validasi form request (Login, Register, dll)
│   │   │   └── Resources/                        # Transformasi format JSON output
│   │   ├── Models/                               # User, Survey, SurveyCategory, Question, dll.
│   │   └── Services/                             # Lapisan bisnis logika (AuthService, dll)
│   ├── database/
│   │   ├── migrations/                           # Skema struktur tabel database
│   │   └── seeders/                              # Data awal admin, user sample, dan survei
│   └── routes/
│       └── api.php                               # Definisi seluruh rute REST API
│
└── frontend/                                 # Aplikasi Frontend SPA Vue 3
    ├── src/
    │   ├── layouts/
    │   │   ├── AdminLayout.vue                   # Tata letak Admin (Sidebar & Header navigasi)
    │   │   └── UserLayout.vue                    # Tata letak Pengguna (Header & Kontainer)
    │   ├── views/
    │   │   ├── LoginView.vue                     # Halaman Masuk terpadu
    │   │   ├── RegisterView.vue                  # Halaman Pendaftaran akun baru
    │   │   ├── admin/
    │   │   │   ├── DashboardView.vue             # Statistik ringkas sistem admin
    │   │   │   ├── UsersView.vue                 # Manajemen User, ACC Akun, & Tambah Admin
    │   │   │   ├── SurveysView.vue               # Daftar survei & status tayang
    │   │   │   ├── SurveyCreateView.vue          # Form pembuatan survei & kategori soal
    │   │   │   ├── SurveyEditView.vue            # Form edit survei (status draft)
    │   │   │   └── SurveyResultsView.vue         # Hasil rekapitulasi & grafik Chart.js
    │   │   └── user/
    │   │       ├── DashboardView.vue             # Daftar kuesioner aktif yang tersedia
    │   │       ├── ProfileView.vue               # Ubah informasi profil pribadi
    │   │       ├── SurveyFillView.vue            # Lembar pengisian kuesioner (A, B, C, D)
    │   │       └── HistoryView.vue               # Riwayat survei yang telah diisi
    │   ├── components/ui/                        # Komponen antarmuka (Button, Card, Table, dll)
    │   ├── services/                             # Axios client & service API terpusat
    │   ├── stores/                               # State management Pinia (Auth, Toast)
    │   ├── router/index.ts                       # Konfigurasi Vue Router & Navigation Guards
    │   └── types/                                # Definisi tipe TypeScript
    └── package.json
```

---

## 🛡️ Catatan Keamanan & Praktik Terbaik

1. **Proteksi Sesi Terpusat**: Menggunakan cookie HTTP-Only dari Laravel Sanctum yang kebal terhadap serangan pencurian token via skrip XSS di peramban.
2. **Pencabutan Akses Instan (*Session Revocation*)**: Begitu admin menonaktifkan seorang pengguna atau mengubah kata sandi, seluruh token Sanctum dan entri sesi basis data langsung dihapus detik itu juga.
3. **Pencegahan Kunci Sendiri (*Self-Lock Prevention*)**: Admin dicegah untuk mendowngrade, menonaktifkan, atau menghapus akunnya sendiri, serta dilindungi dari penghapusan admin terakhir yang dapat menyebabkan sistem terkunci tanpa pengelola.
4. **Pencegahan Duplikasi Jawaban**: Penegakan integritas data ganda menggunakan `UNIQUE(survey_id, user_id)` pada basis data untuk memastikan 1 responden hanya dapat mengirimkan jawaban 1 kali untuk setiap kuesioner.
5. **Kunci Kuesioner Terpublikasi**: Kuesioner berstatus *published* tidak dapat diubah struktur soalnya guna mencegah inkonsistensi data pada respons yang sudah masuk.
