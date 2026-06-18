# Prompt: Implementasi Data Tier - Sistem Manajemen Klinik dan Apotek

Gunakan teks di bawah ini sebagai prompt mandiri. Jika dipindahkan ke sesi atau tool lain (misalnya Claude Code), seluruh konteks yang diperlukan sudah disertakan di dalamnya.

---

## 1. Peran dan Konteks

Anda adalah backend engineer senior yang berspesialisasi dalam keamanan aplikasi PHP murni dan MySQL di shared hosting cPanel, dengan pengalaman menerjemahkan class diagram UML (inheritance, aggregation, composition, association, dependency) menjadi skema relasional dan kode backend yang rapi.

Proyek ini adalah Sistem Manajemen Klinik dan Apotek berbasis web dengan 3 tier architecture:

- Presentation tier: HTML/CSS dengan Tailwind atau Bootstrap, responsif desktop dan mobile.
- Application tier: PHP murni (tanpa framework), pola server-side logic per domain (dashboard, antrian, EHR ringkas, inventaris).
- Data tier: MySQL/MariaDB, diakses lewat PDO dengan prepared statements.

Environment development dilakukan secara lokal, deployment final ke shared hosting cPanel. Semua keputusan desain pada data tier harus mempertimbangkan batasan cPanel: akses SSH terbatas atau tidak ada, MySQL dikelola lewat phpMyAdmin/cPanel MySQL Databases, dan struktur folder web root yang baku.

## 2. Cakupan Tugas (Scope)

Kerjakan HANYA Data Tier pada tahap ini:

- Skema database (migration files).
- Connection layer (PDO wrapper) dan Config/Env loader.
- Base Repository/Model untuk operasi CRUD generik.
- Seeder akun default per role.
- File konfigurasi environment (.env dan .env.example).

Jangan membuat controller, routing, view, atau logika bisnis aplikasi pada prompt ini. Tier tersebut akan dikerjakan pada prompt terpisah setelah data tier selesai dan divalidasi.

## 3. Aktor dan Role (dari Use Case Diagram)

- Public / Visitor: tidak memerlukan akun, hanya mengakses jadwal dokter dan tampilan antrian publik.
- Patient: mendaftar akun, membuat dan melihat riwayat appointment.
- Receptionist: mengelola seluruh appointment, verifikasi identitas, menangani antrian darurat.
- Doctor: mengelola rekam medis (EHR ringkas), melihat antrian konsultasi.
- Admin: konfigurasi sistem, kelola akun pengguna, kelola jadwal dokter, proses pembayaran dan klaim asuransi.
- Apoteker: melihat resep digital, update status obat, dispensing, kelola stok inventaris.

Hanya lima role di atas (Patient, Receptionist, Doctor, Admin, Apoteker) yang membutuhkan akun login. Public/Visitor tidak disertakan dalam tabel users.

## 4. Skema Data yang Wajib Dirancang

### 4.1 Identitas dan Akses (pola inheritance)

| Tabel | Kolom Utama | Relasi UML | Catatan |
|---|---|---|---|
| users | id (BIGINT PK AI), public_id (UUID), name, email (unique), password_hash, role (ENUM), is_active, force_password_change, failed_login_attempts, locked_until, created_at, updated_at, deleted_at | Abstract base (setara `<<abstract>> User`) | Satu tabel induk untuk seluruh role |
| patients | user_id (PK, FK -> users.id), nik_encrypted, nik_hash (unique), date_of_birth, gender, phone, address | Inheritance (extends User) | nik_encrypted dan nik_hash dijelaskan di bagian keamanan |
| doctors | user_id (PK, FK -> users.id), specialization, license_number (unique), bio | Inheritance (extends User) | |
| receptionists | user_id (PK, FK -> users.id), employee_code | Inheritance (extends User) | |
| admins | user_id (PK, FK -> users.id), employee_code | Inheritance (extends User) | |
| apotekers | user_id (PK, FK -> users.id), license_number (unique) | Inheritance (extends User) | |
| login_attempts | id, email_attempted, ip_address, user_agent, success, created_at | Dependency dari users | Insert-only, dipakai untuk lockout |
| audit_logs | id, user_id (FK nullable), action, entity_table, entity_id, ip_address, created_at | Dependency dari users | Insert-only, tidak ada update/delete |

### 4.2 Penjadwalan dan Antrian

| Tabel | Kolom Utama | Relasi UML | Catatan |
|---|---|---|---|
| doctor_schedules | id, doctor_id (FK), schedule_date atau day_of_week, start_time, end_time, quota, room, is_active | Association ke doctors | Sumber data untuk "View Today's Doctor Schedules" |
| appointments | id, public_id (UUID), patient_id (FK), doctor_id (FK), schedule_id (FK nullable), queue_number, complaint, priority (ENUM: normal, emergency), status (ENUM: waiting, called, in_consultation, done, cancelled), called_at, started_at, completed_at, created_at, updated_at | Association ke patients dan doctors | called_at/started_at/completed_at dipakai application tier untuk hitung estimasi waktu tunggu |

### 4.3 Rekam Medis dan Resep (pola composition, setara KRS -> KRSDetail)

| Tabel | Kolom Utama | Relasi UML | Catatan |
|---|---|---|---|
| medical_records | id, appointment_id (FK unique), doctor_id (FK), diagnosis, notes, created_at, updated_at | Association ke appointments | Satu appointment maksimal satu medical record |
| prescriptions | id, medical_record_id (FK), status (ENUM: pending, dispensed), dispensed_by (FK apotekers nullable), dispensed_at, created_at | Composition dari medical_records | |
| prescription_items | id, prescription_id (FK), medicine_id (FK), dosage, quantity, created_at | Composition dari prescriptions, referensi ke medicines | Setara KRSDetail -> MataKuliah |

### 4.4 Inventaris Obat

| Tabel | Kolom Utama | Relasi UML | Catatan |
|---|---|---|---|
| medicines | id, name, unit, stock, minimum_stock, price, created_at, updated_at, deleted_at | - | Soft delete, bukan hard delete |
| medicine_stock_logs | id, medicine_id (FK), change_type (ENUM: in, out, adjustment), quantity_change, reference_note, performed_by (FK users), created_at | Dependency/audit dari medicines | Insert-only, jadi sumber kebenaran untuk rekonsiliasi stok |

### 4.5 Pembayaran

| Tabel | Kolom Utama | Relasi UML | Catatan |
|---|---|---|---|
| payments | id, appointment_id (FK), amount, method, status, processed_by (FK users), created_at | Association ke appointments | |
| insurance_claims | id, payment_id (FK), provider_name, policy_number, claim_status, created_at, updated_at | Extend dari payments | Sesuai relasi extend pada use case diagram |

### 4.6 Konfigurasi Sistem

| Tabel | Kolom Utama | Relasi UML | Catatan |
|---|---|---|---|
| system_settings | id, setting_key (unique), setting_value, description, updated_at | - | Untuk menyimpan parameter seperti ambang batas stok minimum default dan rata-rata durasi konsultasi, supaya tidak hardcode di kode aplikasi |

## 5. Persyaratan Keamanan (Security Best Practice)

- Seluruh query database wajib memakai prepared statements lewat PDO. Tidak boleh ada string concatenation untuk membentuk query.
- Password disimpan dengan `password_hash()` menggunakan `PASSWORD_ARGON2ID`, dengan fallback ke `PASSWORD_BCRYPT` jika versi PHP di cPanel belum mendukung Argon2id.
- Data sensitif pasien (NIK) disimpan terenkripsi (`openssl_encrypt`, AES-256-GCM) dengan kunci dari `.env`. Sediakan kolom `nik_hash` (HMAC-SHA256 dengan kunci terpisah) untuk keperluan pencarian/duplikasi tanpa perlu dekripsi.
- Primary key internal tetap auto-increment BIGINT untuk performa, tapi sediakan kolom `public_id` (UUID v4) untuk seluruh referensi yang diekspos ke luar (URL publik, nomor antrian yang ditampilkan), agar tidak terjadi enumerasi data.
- Semua tabel master (users, medicines) memakai soft delete (`deleted_at`), bukan hard delete, agar jejak audit tetap terjaga.
- Foreign key memakai `ON DELETE RESTRICT` untuk data klinis kritikal (medical_records, prescriptions, payments) dan `ON DELETE CASCADE` hanya untuk relasi composition yang aman (prescription_items terhadap prescriptions).
- Index dibuat pada kolom yang sering difilter: `users.email` (unique), `users.role`, `appointments.status`, `appointments.scheduled_date`, `medicines.stock`.
- User database MySQL di cPanel memakai prinsip least privilege: satu user untuk runtime aplikasi (hanya SELECT, INSERT, UPDATE, DELETE), dan jika memungkinkan satu user terpisah untuk migration (CREATE, ALTER, DROP) yang tidak dipakai di kode aplikasi produksi.
- Tabel `audit_logs` dan `medicine_stock_logs` bersifat insert-only. Repository layer untuk kedua tabel ini tidak boleh menyediakan method update atau delete.
- Lockout sederhana memakai `failed_login_attempts` dan `locked_until` pada users, dikombinasikan dengan tabel `login_attempts`.

## 6. Environment Variables (.env)

Definisikan minimal variabel berikut, dan jelaskan masing-masing di `.env.example` dengan nilai contoh (placeholder, bukan nilai asli):

```
APP_ENV=development
APP_URL=http://localhost
APP_ENCRYPTION_KEY=
APP_HMAC_KEY=

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_CHARSET=utf8mb4

DEFAULT_PATIENT_EMAIL=
DEFAULT_PATIENT_PASSWORD=
DEFAULT_DOCTOR_EMAIL=
DEFAULT_DOCTOR_PASSWORD=
DEFAULT_RECEPTIONIST_EMAIL=
DEFAULT_RECEPTIONIST_PASSWORD=
DEFAULT_ADMIN_EMAIL=
DEFAULT_ADMIN_PASSWORD=
DEFAULT_APOTEKER_EMAIL=
DEFAULT_APOTEKER_PASSWORD=
```

Aturan:

- `.env` berisi nilai asli dan wajib masuk `.gitignore`. `.env.example` berisi placeholder dan boleh ikut version control.
- Jika `DEFAULT_*_PASSWORD` dikosongkan, seeder wajib men-generate password acak yang aman (lihat bagian 7), bukan memakai nilai default yang bisa ditebak.
- Tidak ada satu pun kredensial, key, atau nama database yang ditulis langsung di kode PHP. Semua diakses lewat satu Config/Env loader.

## 7. Seeder Akun Default per Role

Untuk setiap role (Patient, Doctor, Receptionist, Admin, Apoteker), seeder harus:

1. Membaca `DEFAULT_<ROLE>_EMAIL` dan `DEFAULT_<ROLE>_PASSWORD` dari `.env`.
2. Jika password tidak diset, generate password acak minimal 16 karakter (kombinasi huruf besar, huruf kecil, angka, simbol) memakai `random_bytes`, bukan pola yang bisa ditebak seperti "Admin123!".
3. Hash password sebelum disimpan ke kolom `password_hash`.
4. Insert ke tabel `users` dan tabel ekstensi role yang sesuai, dengan data dummy yang realistis sesuai struktur tabel role tersebut (misalnya doctor perlu specialization dan license_number).
5. Set `force_password_change = true` agar akun wajib mengganti password saat login pertama.
6. Tulis kredensial plaintext yang baru dibuat ke satu file lokal khusus (misalnya `storage/seeded_credentials.txt`) memakai mode append, lalu file ini wajib masuk `.gitignore`. Setelah ditulis sekali, plaintext tidak boleh dicetak ke log atau output lain.
7. Seeder wajib diblokir berjalan otomatis saat `APP_ENV=production` kecuali dijalankan eksplisit dengan flag konfirmasi (misalnya `--force`), untuk mencegah akun demo bocor ke environment produksi di cPanel.

## 8. Prinsip Coding yang Wajib Diikuti

- DRY: satu base Repository/Model class untuk operasi CRUD generik yang dipakai semua entity, satu Database Connection class (singleton PDO) yang dipakai semua model, satu Config/Env loader class. Tidak boleh ada kode koneksi atau query yang diduplikasi di banyak file.
- No hardcoding: seluruh konfigurasi (host, nama database, kredensial, encryption key, akun default) diambil dari `.env` lewat satu Config class. Tidak ada string koneksi, password, atau key yang ditulis langsung di kode.
- Append, bukan overwrite/redirect: setiap operasi penulisan ke `.env`, file log, dan file ekspor kredensial wajib memakai mode append (misalnya `file_put_contents($path, $data, FILE_APPEND)`), bukan mode yang menimpa/truncate isi file yang sudah ada, kecuali memang file tersebut baru dibuat untuk pertama kali.
- Migration bersifat incremental dan tidak destructive: setiap perubahan skema ditambahkan sebagai file migration baru bernomor urut/timestamp, tidak mengedit ulang file migration lama yang sudah pernah dijalankan.

## 9. Pertimbangan Hosting cPanel

- Jika struktur akun cPanel memungkinkan, letakkan `.env` satu level di atas `public_html`. Jika tidak memungkinkan, tambahkan rule di `.htaccess` untuk menolak akses langsung ke file `.env`.
- Penamaan database dan user MySQL mengikuti konvensi cPanel (`cpanelusername_namadb`, `cpanelusername_namauser`), agar perpindahan dari development ke production tidak butuh banyak penyesuaian kode.
- Sediakan dua cara menjalankan migration: script PHP CLI custom (untuk hosting yang masih punya akses terminal/cron) dan file `.sql` gabungan sebagai fallback untuk diimpor manual lewat phpMyAdmin.
- Pastikan seluruh sintaks SQL kompatibel dengan MySQL 8.0 atau MariaDB 10.x, dua versi yang paling umum tersedia di cPanel, dan hindari fitur yang spesifik ke satu engine saja.

## 10. Struktur Folder yang Diharapkan

```
/database
  /migrations
    001_create_users_table.php
    002_create_patients_table.php
    ... (satu file per tabel, urut sesuai dependency FK)
  /seeders
    seed_default_accounts.php
/src
  /Config
    Env.php
  /Database
    Connection.php
  /Repository
    BaseRepository.php
.env.example
.gitignore
```

## 11. Output yang Diharapkan

1. ERD (boleh dalam format teks atau mermaid) yang menggambarkan seluruh tabel dan relasi pada bagian 4.
2. File migration terurut sesuai struktur folder di atas, idempotent dan tidak destructive.
3. File seeder akun default sesuai aturan bagian 7.
4. Class Connection (PDO wrapper) dan Config/Env loader.
5. `.env.example` lengkap dengan komentar penjelasan tiap variabel.
6. README singkat berisi langkah menjalankan migration dan seeder, baik di development lokal maupun di cPanel.

## 12. Checklist Validasi Akhir

- [ ] Semua query memakai prepared statements, tidak ada string concatenation.
- [ ] Tidak ada kredensial atau key yang hardcoded di kode.
- [ ] Semua penulisan ke `.env`/log/file kredensial memakai mode append.
- [ ] Lima role (Patient, Doctor, Receptionist, Admin, Apoteker) punya akun default dengan password yang dihasilkan aman, bukan plaintext yang bisa ditebak.
- [ ] Seeder akun default tidak otomatis berjalan saat `APP_ENV=production`.
- [ ] Tabel audit_logs dan medicine_stock_logs bersifat insert-only.
- [ ] Struktur skema sudah merepresentasikan inheritance, composition, association, dan extend sesuai use case diagram.
