<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $c['username'], $c['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('<h3>Koneksi database gagal.</h3><p>Cek <b>config/database.php</b> dan pastikan database <b>'
                . htmlspecialchars($c['database']) . '</b> sudah di-import dari folder <b>database/</b>.</p>');
        }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function rupiah($n): string
{
    return 'Rp' . number_format((int) $n, 0, ',', '.');
}

const BULAN_ID = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI_ID = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function tgl($date, bool $panjang = false): string
{
    if (!$date) return '-';
    $t = strtotime($date);
    $b = BULAN_ID[(int) date('n', $t)];
    return date('j', $t) . ' ' . ($panjang ? $b : substr($b, 0, 3)) . ' ' . date('Y', $t);
}

function tgl_hari($date): string
{
    if (!$date) return '-';
    return HARI_ID[(int) date('w', strtotime($date))] . ', ' . tgl($date, true);
}

function jam($t): string
{
    return $t ? substr($t, 0, 5) : '-';
}

function periode_id(string $ym): string
{
    return BULAN_ID[(int) substr($ym, 5, 2)] . ' ' . substr($ym, 0, 4);
}

function ukuran_file(int $b): string
{
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024) return round($b / 1024) . ' KB';
    return $b . ' B';
}

function url(string $page = 'dashboard', array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

function redirect(string $page, array $params = []): void
{
    header('Location: ' . url($page, $params));
    exit;
}

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------------------------------------------------------------- akun & peran

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!user()) {
        flash('Silakan masuk terlebih dahulu.', 'error');
        redirect('login');
    }
    // Ambil ulang dari database agar perubahan peran / penonaktifan langsung berlaku
    $u = q('SELECT id, nama, email, role, status FROM anggota WHERE id = ?', [user()['id']])->fetch();
    if (!$u || $u['status'] !== 'aktif') {
        $_SESSION = [];
        flash('Akun Anda tidak aktif lagi. Hubungi pengurus MGMP.', 'error');
        redirect('login');
    }
    $_SESSION['user'] = ['id' => (int) $u['id'], 'nama' => $u['nama'], 'email' => $u['email'], 'role' => $u['role']];
}

function role(): string
{
    return user()['role'] ?? '';
}

/** Pengurus inti: ketua, sekretaris, bendahara. */
function pengurus(): bool
{
    return in_array(role(), ['ketua', 'sekretaris', 'bendahara'], true);
}

/** Kegiatan, materi, anggota, pengumuman dikelola ketua & sekretaris. */
function kelola_kegiatan(): bool
{
    return in_array(role(), ['ketua', 'sekretaris'], true);
}

/** Iuran & kas dikelola ketua & bendahara. */
function kelola_kas(): bool
{
    return in_array(role(), ['ketua', 'bendahara'], true);
}

function require_role(callable $cek, string $pesan = 'Menu ini khusus pengurus.'): void
{
    if (!$cek()) {
        flash($pesan, 'error');
        redirect('dashboard');
    }
}

const LABEL_ROLE = ['ketua' => 'Ketua', 'sekretaris' => 'Sekretaris', 'bendahara' => 'Bendahara', 'anggota' => 'Anggota'];

// ---------------------------------------------------------------- keamanan

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf_token(), $_POST['_csrf'] ?? '')) {
        http_response_code(403);
        die('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/** Batas percobaan sederhana berbasis sesi (mis. login & cek sertifikat). */
function batas_coba(string $kunci, int $maks = 5, int $detik = 600): bool
{
    $now = time();
    $d = $_SESSION['coba'][$kunci] ?? ['n' => 0, 't' => $now];
    if ($now - $d['t'] > $detik) $d = ['n' => 0, 't' => $now];
    if ($d['n'] >= $maks) {
        $_SESSION['coba'][$kunci] = $d;
        return false;
    }
    $d['n']++;
    $_SESSION['coba'][$kunci] = $d;
    return true;
}

function reset_coba(string $kunci): void
{
    unset($_SESSION['coba'][$kunci]);
}

function kode_acak(int $n = 6): string
{
    $h = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $n; $i++) $s .= $h[random_int(0, strlen($h) - 1)];
    return $s;
}

function hp_62(?string $v): string
{
    $d = preg_replace('/\D/', '', (string) $v);
    if ($d === '') return '';
    return str_starts_with($d, '0') ? '62' . substr($d, 1) : $d;
}

function wa_link(?string $hp, string $pesan = ''): string
{
    $no = hp_62($hp);
    return $no === '' ? '' : 'https://wa.me/' . $no . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
}

// ---------------------------------------------------------------- data umum

function setting(): array
{
    static $s = null;
    return $s ??= q('SELECT * FROM pengaturan WHERE id = 1')->fetch() ?: [];
}

function saldo_kas(): int
{
    return (int) q("SELECT COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) FROM kas")->fetchColumn();
}

/** Rekap kehadiran satu anggota pada kegiatan yang sudah selesai. */
function rekap_hadir(int $anggotaId): array
{
    $r = q("SELECT COUNT(*) total,
                   SUM(p.status = 'hadir') hadir,
                   SUM(p.status = 'izin') izin,
                   SUM(p.status = 'alfa') alfa,
                   COALESCE(SUM(CASE WHEN p.status = 'hadir' THEN k.jam_pelatihan ELSE 0 END), 0) jp
            FROM peserta p JOIN kegiatan k ON k.id = p.kegiatan_id
            WHERE p.anggota_id = ? AND k.status = 'selesai'", [$anggotaId])->fetch();
    $r['persen'] = $r['total'] ? round($r['hadir'] / $r['total'] * 100) : 0;
    return $r;
}

/** Presensi dianggap terbuka bila kode sudah dibuat dan belum melewati batas waktu. */
function presensi_terbuka(array $k): bool
{
    return !empty($k['kode_presensi']) && !empty($k['presensi_tutup']) && strtotime($k['presensi_tutup']) > time();
}

const JENIS_KEGIATAN = ['rutin' => 'MGMP Rutin', 'workshop' => 'Workshop', 'iht' => 'IHT', 'diklat' => 'Diklat', 'lomba' => 'Lomba', 'rapat' => 'Rapat'];
const STATUS_KEGIATAN = ['rencana' => ['Rencana', 'abu'], 'dibuka' => ['Pendaftaran dibuka', 'biru'], 'selesai' => ['Selesai', 'hijau'], 'batal' => ['Batal', 'merah']];
const STATUS_PESERTA = ['terdaftar' => ['Terdaftar', 'biru'], 'hadir' => ['Hadir', 'hijau'], 'izin' => ['Izin', 'kuning'], 'alfa' => ['Tanpa keterangan', 'merah']];
const STATUS_MATERI = ['menunggu' => ['Menunggu moderasi', 'kuning'], 'disetujui' => ['Disetujui', 'hijau'], 'ditolak' => ['Ditolak', 'merah']];
const STATUS_ANGGOTA = ['pending' => ['Menunggu verifikasi', 'kuning'], 'aktif' => ['Aktif', 'hijau'], 'nonaktif' => ['Nonaktif', 'abu']];
const KATEGORI_MATERI = ['perangkat' => 'Perangkat ajar', 'modul' => 'Modul ajar', 'soal' => 'Bank soal', 'media' => 'Media pembelajaran', 'regulasi' => 'Regulasi & panduan', 'lainnya' => 'Lainnya'];

function chip(array $map, string $k): string
{
    [$label, $warna] = $map[$k] ?? [ucfirst($k), 'abu'];
    return '<span class="chip chip-' . $warna . '">' . e($label) . '</span>';
}

// ---------------------------------------------------------------- berkas

const EKSTENSI_MATERI = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];

/**
 * Simpan berkas unggahan ke uploads/<folder>. Nama file diacak supaya nama asli
 * (yang bisa mengandung karakter aneh) tidak pernah dipakai di sistem berkas.
 * @return array{0:string,1:string,2:int} [namaFile, namaAsli, ukuran]
 * @throws RuntimeException
 */
function simpan_berkas(array $f, string $folder, array $ekstensi, int $maksMb = 10): array
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_INI_SIZE || ($f['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Ukuran berkas melebihi batas server.');
    }
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Berkas gagal diunggah.');
    }
    if ($f['size'] > $maksMb * 1024 * 1024) {
        throw new RuntimeException("Ukuran berkas maksimal {$maksMb} MB.");
    }
    $asli = trim(preg_replace('/[\x00-\x1f\/\\\\]+/', ' ', (string) $f['name']));
    $ext = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
    if (!in_array($ext, $ekstensi, true)) {
        throw new RuntimeException('Jenis berkas tidak diizinkan. Gunakan: ' . implode(', ', $ekstensi) . '.');
    }
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $nama = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nama)) {
        throw new RuntimeException('Berkas gagal disimpan. Pastikan folder uploads bisa ditulis.');
    }
    return [$nama, mb_substr($asli, 0, 150), (int) $f['size']];
}

function hapus_berkas(string $folder, ?string $nama): void
{
    if (!$nama || str_contains($nama, '/') || str_contains($nama, '\\') || str_starts_with($nama, 'contoh-')) return;
    $p = __DIR__ . '/../uploads/' . $folder . '/' . $nama;
    if (is_file($p)) @unlink($p);
}

// ---------------------------------------------------------------- ikon

function icon(string $name, int $size = 18): string
{
    // Ikon garis gaya Lucide, disematkan agar aplikasi tetap jalan tanpa internet
    static $p = [
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'folder' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
        'megaphone' => '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68 1.65 1.65 0 0 0 10 3.17V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'plus' => '<path d="M5 12h14M12 5v14"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
        'printer' => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.48 13.37 17 22l-5-3-5 3 1.52-8.63"/>',
        'wa' => '<path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/>',
        'alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'book' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'school' => '<path d="M14 22v-4a2 2 0 1 0-4 0v4"/><path d="m18 10 3.5 2v8a2 2 0 0 1-2 2h-15a2 2 0 0 1-2-2v-8L6 10"/><path d="M12 2 2 8l10 6 10-6-10-6Z"/>',
        'arrow-left' => '<path d="m12 19-7-7 7-7M19 12H5"/>',
        'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
    ];
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? $p['info']) . '</svg>';
}
