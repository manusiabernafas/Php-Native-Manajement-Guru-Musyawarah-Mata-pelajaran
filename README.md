# Aplikasi MGMP Online (PHP Native + MySQL)

Aplikasi MGMP Online (Musyawarah Guru Mata Pelajaran) berbasis PHP Native dan MySQL untuk mengelola organisasi guru dalam satu tempat: pendaftaran anggota, agenda kegiatan, presensi dengan kode, sertifikat ber-JP yang bisa dicek keasliannya, bank materi perangkat ajar, sampai iuran dan buku kas. Cocok untuk MGMP SMP/SMA/SMK, KKG SD, MKKS, maupun komunitas guru lain di tingkat kabupaten atau kota.

Aplikasi punya halaman publik yang bisa langsung jadi website resmi MGMP (profil, agenda terdekat, pengumuman, materi unggulan, cek sertifikat) dan panel anggota dengan empat peran: Ketua, Sekretaris, Bendahara, dan Anggota. Kodenya ditulis tanpa framework dan tanpa Composer sehingga mudah dipelajari untuk tugas kuliah atau skripsi, dan cukup lengkap untuk langsung dipakai pengurus MGMP yang selama ini masih memakai Google Form, grup WhatsApp, dan Excel. Tampilannya bergaya map arsip: kertas krem, tinta biru tua, dan aksen stabilo kuning.

## Cara kerja

Guru mendaftar sendiri lewat halaman publik dengan NUPTK, sekolah, dan nomor WhatsApp, lalu statusnya menunggu verifikasi ketua atau sekretaris. Setelah aktif, anggota bisa melihat agenda, mendaftar kegiatan (dengan kuota opsional), mengunduh materi, melihat tagihan iuran, dan mencetak sertifikat sendiri.

Saat kegiatan berlangsung, pengurus menekan tombol Buka Presensi: sistem membuat kode 6 karakter yang berlaku sesuai menit yang diatur (default 120 menit). Kode cukup ditulis di papan atau layar proyektor, peserta memasukkannya dari HP, dan kehadiran tercatat beserta jamnya. Percobaan kode dibatasi agar tidak bisa ditebak. Ketika kegiatan ditandai selesai, pendaftar yang tidak presensi otomatis dicatat tanpa keterangan, dan pengurus masih bisa mengoreksi status hadir/izin/alfa per peserta.

Peserta yang hadir bisa mencetak sertifikat sesuai jumlah JP kegiatan. Nomor sertifikat dibuat urut otomatis (contoh 014/SRT/MGMP-MAT/X/2026) beserta kode verifikasi unik yang bisa dicek siapa saja di halaman Cek Sertifikat, misalnya oleh kepala sekolah atau tim penilai PKG. Bank materi menerima unggahan PDF, Word, PowerPoint, Excel, ZIP, dan gambar; materi dari anggota menunggu moderasi pengurus, nama berkas diacak di server, dan unduhan selalu lewat PHP agar hak aksesnya diperiksa.

Bendahara menerbitkan tagihan iuran bulanan untuk seluruh anggota aktif dengan sekali klik (tidak bisa dobel), mencatat pelunasan tunai atau transfer yang otomatis masuk buku kas, dan mengirim pengingat lewat tautan WhatsApp. Buku kas mencatat pemasukan dan pengeluaran lain dengan saldo yang tidak boleh minus. Halaman laporan merangkum kegiatan, kehadiran, anggota teraktif, anggota yang perlu dihubungi, partisipasi per sekolah, serta arus kas, dan semuanya bisa diekspor ke CSV.

## Fitur

- Halaman publik siap jadi website MGMP: profil, agenda terdekat, pengumuman, materi unggulan, kontak sekretariat & WhatsApp
- Pendaftaran anggota mandiri (NUPTK, sekolah, WhatsApp) dengan verifikasi pengurus dan anti-bot honeypot
- Login 4 peran: Ketua, Sekretaris, Bendahara, dan Anggota dengan hak akses berbeda
- Dashboard: kegiatan terdekat, rekap kehadiran 8 kegiatan terakhir, pengumuman, materi terbaru, tagihan iuran
- Agenda kegiatan: MGMP rutin, workshop, IHT, diklat, lomba, rapat lengkap dengan narasumber, kuota, biaya, dan JP
- Pendaftaran kegiatan oleh anggota dengan kuota (tidak bisa melebihi kuota, tidak bisa daftar dobel)
- Presensi dengan kode 6 karakter yang kedaluwarsa otomatis, percobaan kode dibatasi
- Tandai kegiatan selesai: pendaftar yang tidak presensi otomatis tercatat tanpa keterangan
- Koreksi status peserta (hadir, izin, tanpa keterangan) dan cetak daftar hadir bertanda tangan
- Sertifikat kegiatan siap cetak dengan jumlah JP, nomor urut otomatis, dan kode verifikasi unik
- Halaman Cek Sertifikat publik untuk memverifikasi keaslian sertifikat
- Profil anggota: rekap kehadiran, total JP pengembangan diri, dan daftar sertifikat
- Bank materi: perangkat ajar, modul, bank soal, media, regulasi dengan pencarian dan filter kategori
- Moderasi materi: unggahan anggota menunggu persetujuan, bisa ditolak dengan catatan perbaikan
- Unggahan aman: whitelist ekstensi, batas ukuran, nama berkas acak, unduhan lewat PHP dengan cek hak akses
- Iuran bulanan: terbitkan tagihan untuk semua anggota aktif sekali klik, tidak bisa dobel
- Pelunasan iuran tunai/transfer otomatis tercatat di buku kas, aman dari klik ganda
- Pengingat iuran lewat tautan WhatsApp (wa.me) berisi pesan siap kirim
- Buku kas pemasukan & pengeluaran, saldo tidak bisa minus, rekap kategori terbesar
- Pengumuman dengan tanda penting dan opsi tampil di halaman publik
- Laporan: kegiatan per jenis, anggota teraktif, anggota yang perlu dihubungi, partisipasi per sekolah, arus kas
- Ekspor CSV kegiatan, kehadiran, iuran, dan kas (aman dari injeksi rumus Excel)
- Pengaturan identitas MGMP, susunan pengurus, nominal iuran, dan masa berlaku kode presensi
- Keamanan: PDO prepared statement, password_hash, CSRF, batas percobaan login, transaksi database
- Data contoh 42 guru, 14 kegiatan, 340+ data kehadiran, 24 materi, 6 bulan iuran & kas (tanggal menyesuaikan hari import)
- Tampilan map arsip krem-biru tua-stabilo, responsif di HP, ikon lokal (bisa offline)
- Tanpa framework dan tanpa Composer, cukup XAMPP

## Instalasi

**Kebutuhan:** PHP 8.0 atau lebih baru dan MySQL/MariaDB. Paling mudah pakai XAMPP atau Laragon.

1. Ekstrak file ZIP lalu pindahkan folder `mgmp` ke folder web server:
   - XAMPP: `C:\xampp\htdocs\mgmp`
   - Laragon: `C:\laragon\www\mgmp`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin`, klik **Import**, pilih file `database/mgmp_online.sql`, lalu klik **Import**. Database `mgmp_online` beserta data contohnya akan dibuat otomatis.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/mgmp_online.sql
   ```
   Tanggal data contoh menyesuaikan hari saat import, jadi agenda terdekat dan tagihan bulan berjalan selalu terlihat.

4. Jika user/password MySQL kamu bukan bawaan XAMPP, sesuaikan `config/database.php`:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'mgmp_online',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. Buka `http://localhost/mgmp` untuk halaman publik, lalu klik **Masuk** dan gunakan akun demo:
   - Ketua: `ketua@mgmp.test` / `ketua123` (semua menu + pengaturan)
   - Sekretaris: `sekretaris@mgmp.test` / `guru123` (kegiatan, presensi, anggota, materi, pengumuman)
   - Bendahara: `bendahara@mgmp.test` / `guru123` (iuran & buku kas)
   - Anggota: `guru@mgmp.test` / `guru123`

6. Ubah identitas MGMP, susunan pengurus, nominal iuran, dan masa berlaku kode presensi di menu **Pengaturan MGMP**, lalu ganti kata sandi akun demo.

**Batas unggah materi:** maksimal 10 MB per berkas. Kalau unggahan gagal, naikkan `upload_max_filesize` dan `post_max_size` di `php.ini` XAMPP.

**Tanpa XAMPP?** Dari dalam folder project jalankan `php -S localhost:8000` lalu buka `http://localhost:8000`.

## Struktur folder

```
mgmp/
├── index.php              # router: index.php?page=...
├── config/database.php    # koneksi MySQL
├── database/mgmp_online.sql
├── includes/              # bootstrap (helper, CSRF, hak akses), header & footer
├── pages/                 # halaman publik + panel (kegiatan, materi, iuran, anggota, laporan)
├── assets/                # CSS & JS
└── uploads/materi/        # berkas bank materi (diakses lewat PHP, bukan langsung)
```
