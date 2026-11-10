<?php
require_role('kelola_kas', 'Iuran & kas dikelola ketua dan bendahara.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('iuran');
$me = user();

switch ($_POST['aksi'] ?? '') {
    case 'terbitkan':
        $periode = trim($_POST['periode'] ?? '');
        $jumlah = (int) ($_POST['jumlah'] ?? 0);
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) { flash('Periode tidak valid.', 'error'); break; }
        if ($jumlah < 1000 || $jumlah > 10000000) { flash('Nominal iuran tidak wajar.', 'error'); break; }
        $anggota = q("SELECT id FROM anggota WHERE status = 'aktif'")->fetchAll(PDO::FETCH_COLUMN);
        $baru = 0;
        db()->beginTransaction();
        foreach ($anggota as $aid) {
            // INSERT IGNORE mengandalkan unique(anggota_id, periode) agar tidak dobel
            $st = q('INSERT IGNORE INTO iuran (anggota_id, periode, jumlah, status) VALUES (?,?,?,"belum")', [$aid, $periode, $jumlah]);
            $baru += $st->rowCount();
        }
        db()->commit();
        flash($baru ? "Tagihan $periode diterbitkan untuk $baru anggota." : 'Semua anggota sudah punya tagihan periode ini.', $baru ? 'ok' : 'info');
        break;

    case 'bayar':
        $id = (int) ($_POST['id'] ?? 0);
        $metode = in_array($_POST['metode'] ?? '', ['tunai', 'transfer'], true) ? $_POST['metode'] : 'tunai';
        $i = q('SELECT i.*, a.nama FROM iuran i JOIN anggota a ON a.id = i.anggota_id WHERE i.id = ?', [$id])->fetch();
        if (!$i) { flash('Tagihan tidak ditemukan.', 'error'); break; }
        if ($i['status'] === 'lunas') { flash('Tagihan ini sudah lunas.', 'error'); break; }
        // Pelunasan dan pencatatan kas harus jadi satu kesatuan; syarat status = "belum"
        // mencegah kas tercatat dobel bila tombol bayar terkirim dua kali
        db()->beginTransaction();
        $up = q('UPDATE iuran SET status = "lunas", dibayar_at = NOW(), metode = ?, dicatat_oleh = ? WHERE id = ? AND status = "belum"', [$metode, $me['id'], $id]);
        if ($up->rowCount() === 0) { db()->rollBack(); flash('Tagihan ini sudah lunas.', 'error'); break; }
        q('INSERT INTO kas (tanggal, jenis, kategori, jumlah, keterangan, iuran_id, dicatat_oleh, created_at)
           VALUES (CURDATE(), "masuk", "Iuran anggota", ?, ?, ?, ?, NOW())',
            [$i['jumlah'], 'Iuran ' . $i['periode'] . ' - ' . $i['nama'], $id, $me['id']]);
        db()->commit();
        flash('Iuran ' . periode_id($i['periode']) . ' atas nama ' . $i['nama'] . ' dicatat lunas.');
        break;

    case 'batal_bayar':
        $id = (int) ($_POST['id'] ?? 0);
        db()->beginTransaction();
        $up = q('UPDATE iuran SET status = "belum", dibayar_at = NULL, metode = NULL, dicatat_oleh = NULL WHERE id = ? AND status = "lunas"', [$id]);
        if ($up->rowCount() === 0) { db()->rollBack(); flash('Tagihan ini belum dibayar.', 'error'); break; }
        q('DELETE FROM kas WHERE iuran_id = ?', [$id]);
        db()->commit();
        flash('Status pembayaran dibatalkan dan catatan kasnya dihapus.');
        break;

    case 'kas_tambah':
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $jenis = ($_POST['jenis'] ?? 'keluar') === 'masuk' ? 'masuk' : 'keluar';
        $jumlah = (int) ($_POST['jumlah'] ?? 0);
        $kategori = mb_substr(trim($_POST['kategori'] ?? ''), 0, 40);
        $ket = mb_substr(trim($_POST['keterangan'] ?? ''), 0, 200);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) { flash('Tanggal tidak valid.', 'error'); redirect('kas'); }
        if ($jumlah < 1) { flash('Jumlah harus lebih dari nol.', 'error'); redirect('kas'); }
        if ($kategori === '') { flash('Isi kategori transaksi.', 'error'); redirect('kas'); }
        if ($jenis === 'keluar' && $jumlah > saldo_kas()) { flash('Pengeluaran melebihi saldo kas (' . rupiah(saldo_kas()) . ').', 'error'); redirect('kas'); }
        q('INSERT INTO kas (tanggal, jenis, kategori, jumlah, keterangan, dicatat_oleh, created_at) VALUES (?,?,?,?,?,?,NOW())',
            [$tanggal, $jenis, $kategori, $jumlah, $ket ?: null, $me['id']]);
        flash('Transaksi kas dicatat.');
        redirect('kas');

    case 'kas_hapus':
        $id = (int) ($_POST['id'] ?? 0);
        $k = q('SELECT * FROM kas WHERE id = ?', [$id])->fetch();
        if (!$k) { flash('Transaksi tidak ditemukan.', 'error'); redirect('kas'); }
        if ($k['iuran_id']) { flash('Catatan dari pembayaran iuran dihapus lewat menu Iuran.', 'error'); redirect('kas'); }
        q('DELETE FROM kas WHERE id = ?', [$id]);
        flash('Transaksi kas dihapus.');
        redirect('kas');

    default:
        flash('Aksi tidak dikenal.', 'error');
}
redirect('iuran');
