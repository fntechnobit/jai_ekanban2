# RUNBOOK — Pulihkan Kanban Orphan cv2 (jai_ekanban)

> **Untuk dieksekusi oleh Claude Code di server, dalam jendela perawatan.**
> Pencegahan (kode) **sudah** terpasang di `main` (commit `bf4b041`). Dokumen ini menangani
> **data lama** yang terlanjur tidak konsisten — TIDAK boleh diubah tanpa persetujuan.
>
> **Prinsip wajib:** backup dulu → transaksi → verifikasi sebelum & sesudah → berhenti & tanya
> manusia bila ragu. Selalu `SELECT` dulu sebelum `UPDATE`/`DELETE`.

---

## 0. Prasyarat & lingkungan

- DB aplikasi (lokal dev: `jai_ekanban`; **di server pakai nama DB yang dikonfigurasi** — cek `.env` `DB_DATABASE`).
- CLI MySQL (dev): `D:\Code\laragon\bin\mysql\mariadb-10.4.32-winx64\bin\mysql.exe -uroot`.
- PHP 8.4 (dev, untuk artisan/tinker): `D:\Code\laragon\bin\php\php-8.4.15-Win32-vs17-x64\php.exe` (PHP default di PATH = 8.1, gagal platform check). Di server pakai PHP yang dipakai aplikasi.
- **Backup wajib:**
  ```bash
  mysqldump <DB> assy_schedule assy_schedule_circuit assy_schedule_shikake \
    kanban_balance_circuit kanban_balance_shikake \
    > backup_orphan_$(date +%Y%m%d_%H%M%S).sql
  ```

### Konteks akar masalah
Bug **lama** pada unverify (diperbaiki 2026-04-22) meng-set `is_lock=0`/`verified_at=NULL`
tanpa menghapus kanban yang sudah dibuat. Akibatnya ada jadwal `is_lock=0` yang **masih
menyimpan** baris `assy_schedule_circuit`/`assy_schedule_shikake`. Karena daftar print
memfilter `is_lock != 0`, kanban itu **tak tampil** (seolah hilang) walau datanya ada.
Guard baru sudah melindungi baris ini dari penghapusan sync/generate; sisa tugas =
buat datanya **konsisten** kembali.

### Grup terdampak (per 2026-06-13) — semua conveyor_id=2 (B3-ENG), shift 1
| tanggal | jadwal | kanban circuit | kanban shikake |
|---|---|---|---|
| 2026-03-03 | 5 | 1278 | 379 |
| 2026-03-06 | 4 | 1039 | 283 |
| 2026-03-09 | 5 | 1172 | 374 |
| 2026-03-10 | 1 | 396 | 48 |
| 2026-03-13 | 4 | 1053 | 278 |
| 2026-04-09 | 2 | 544 | 144 |
| 2026-04-11 | 4 | 1053 | 288 |
| 2026-04-13 | 6 | 945 | 354 |
| 2026-04-14 | 5 | 1162 | 366 |

**Diagnostik ulang (konfirmasi kondisi terkini sebelum tindakan):**
```sql
SELECT DATE(a.schedule) dt, a.conveyor_id, a.shift, COUNT(DISTINCT a.id) sched,
  COALESCE(SUM(cc.cnt),0) circ, COALESCE(SUM(sc.cnt),0) shik
FROM assy_schedule a
LEFT JOIN (SELECT assy_schedule_id,COUNT(*) cnt FROM assy_schedule_circuit GROUP BY assy_schedule_id) cc ON cc.assy_schedule_id=a.id
LEFT JOIN (SELECT assy_schedule_id,COUNT(*) cnt FROM assy_schedule_shikake GROUP BY assy_schedule_id) sc ON sc.assy_schedule_id=a.id
WHERE a.is_lock=0 AND (cc.cnt IS NOT NULL OR sc.cnt IS NOT NULL)
GROUP BY DATE(a.schedule), a.conveyor_id, a.shift ORDER BY dt;
```

---

## Gerbang keputusan: PULIHKAN vs BERSIHKAN

Semua grup adalah tanggal **lampau** (Mar–Apr 2026). Pilih sesuai kebutuhan tim:

- **Opsi A — PULIHKAN (rekomendasi bila riwayat cetak masih dibutuhkan):** kembalikan
  `is_lock=1` sehingga daftar print menampilkan lagi kanban yang sudah ada. Paling tidak
  invasif (kanban sudah ada & benar; hanya flag visibilitas yang dikembalikan).
- **Opsi B — BERSIHKAN (bila tanggal-tanggal ini sudah tidak relevan):** hapus kanban orphan
  agar konsisten sebagai "pending/historis tanpa kanban". Menghilangkan jejak bahwa kanban
  pernah dicetak.

> Keduanya hanya menyentuh 9 grup cv2 di atas. Lakukan **per grup** bila ingin sangat hati-hati,
> atau sekaligus dengan predikat di bawah. Jangan campur: pilih A **atau** B.

---

## OPSI A — Pulihkan visibilitas (set is_lock=1)

```sql
-- 1) Tinjau dulu baris yang akan diubah (harus = 36 jadwal cv2 dgn kanban):
SELECT a.id, DATE(a.schedule) dt, a.shift, a.assy, a.is_lock, a.verified_at
FROM assy_schedule a
WHERE a.conveyor_id=2 AND a.is_lock=0
  AND ( EXISTS(SELECT 1 FROM assy_schedule_circuit c WHERE c.assy_schedule_id=a.id)
     OR EXISTS(SELECT 1 FROM assy_schedule_shikake s WHERE s.assy_schedule_id=a.id) )
ORDER BY dt, a.shift, a.id;

-- 2) Pulihkan (transaksi). verified_at dipertahankan bila ada, jika tidak pakai updated_at.
START TRANSACTION;
UPDATE assy_schedule a
SET a.is_lock     = 1,
    a.verified_at = COALESCE(a.verified_at, a.updated_at),
    a.updated_at  = NOW()
WHERE a.conveyor_id=2 AND a.is_lock=0
  AND ( EXISTS(SELECT 1 FROM assy_schedule_circuit c WHERE c.assy_schedule_id=a.id)
     OR EXISTS(SELECT 1 FROM assy_schedule_shikake s WHERE s.assy_schedule_id=a.id) );
-- 3) Verifikasi: jumlah baris terpengaruh = 36; tidak ada lagi orphan (query diagnostik = kosong).
SELECT ROW_COUNT() AS changed;   -- harap 36
COMMIT;   -- atau ROLLBACK bila tidak sesuai
```
> Catatan saldo (`kanban_balance_*`): pemulihan flag **tidak** menyentuh tabel balance dan
> kanban yang ada sudah memiliki `nomor_urut`. Tidak ada regenerasi → tidak ada risiko
> double-count. Jika nanti grup ini di-**unverify** lewat UI (kode baru), kanban akan dibersihkan
> dengan benar.

---

## OPSI B — Bersihkan kanban orphan (buat konsisten sebagai pending)

```sql
-- 1) Tinjau jumlah yang akan dihapus (harap ~ 8642 circuit + 2514 shikake utk cv2):
SELECT
 (SELECT COUNT(*) FROM assy_schedule_circuit c JOIN assy_schedule a ON a.id=c.assy_schedule_id WHERE a.conveyor_id=2 AND a.is_lock=0) circ_del,
 (SELECT COUNT(*) FROM assy_schedule_shikake s JOIN assy_schedule a ON a.id=s.assy_schedule_id WHERE a.conveyor_id=2 AND a.is_lock=0) shik_del;

-- 2) Hapus (transaksi):
START TRANSACTION;
DELETE c FROM assy_schedule_circuit c JOIN assy_schedule a ON a.id=c.assy_schedule_id
 WHERE a.conveyor_id=2 AND a.is_lock=0;
DELETE s FROM assy_schedule_shikake s JOIN assy_schedule a ON a.id=s.assy_schedule_id
 WHERE a.conveyor_id=2 AND a.is_lock=0;
-- 3) Verifikasi query diagnostik kini kosong.
COMMIT;
```
> ⚠️ Opsi B menghapus jejak kanban. Pilih hanya bila tanggal-tanggal lampau ini sudah tak
> dibutuhkan. Tidak mereset `kanban_balance_*` (saldo carry-over sudah jauh berpindah; cukup
> aman untuk tanggal historis, tapi catat bila ada audit saldo).

---

## ACCEPTANCE CRITERIA
- Query diagnostik (bagian 0) **kosong** → tidak ada lagi jadwal `is_lock=0` yang menyimpan kanban.
- **Opsi A:** 9 grup cv2 kembali muncul di daftar print (filter `is_lock != 0`).
- **Opsi B:** 9 grup tampil sebagai pending tanpa kanban; tidak ada baris kanban yatim.
- Jumlah baris terkunci & total kanban berubah sesuai yang diniatkan, tanpa penurunan tak terduga
  di grup lain.

## ROLLBACK
- Setiap opsi dibungkus transaksi; `ROLLBACK` bila verifikasi gagal.
- Pemulihan penuh: restore dari `backup_orphan_*.sql`.

## Pencegahan yang SUDAH aktif (tidak perlu dikerjakan, hanya referensi)
- `ScheduleCleanupService::applyHasKanbanGuard()` — jadwal ber-kanban tak dihapus sync/generate.
- `ListingSyncService::deleteListingStageData` — proteksi listing_stage diperluas ke jadwal ber-kanban.
- `AssySchedulerService::generateSchedules` — listing ber-kanban dilewati (cegah duplikasi).
- Unverify & resetBalance terkini sudah menghapus kanban dengan benar.
