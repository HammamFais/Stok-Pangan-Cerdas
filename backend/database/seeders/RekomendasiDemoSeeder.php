<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Rekomendasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class RekomendasiDemoSeeder extends Seeder
{
    /**
     * Isi riwayat tindakan demo yang sudah diterapkan, untuk kondisi
     * halaman Riwayat & Statistik yang bersih dan siap demo.
     *
     * Teks isi_saran di bawah ini SENGAJA ditulis tetap (bukan memanggil
     * Gemini API) supaya seeding bisa jalan tanpa koneksi internet/API key,
     * dan data demo selalu konsisten setiap kali di-reset. Isinya diambil
     * apa adanya dari rekomendasi yang pernah benar-benar dihasilkan Gemini
     * selama pengembangan.
     *
     * diterapkan_at sengaja disebar ke beberapa hari yang berbeda (bukan
     * semuanya hari ini) supaya grafik "Tren Penyelamatan Pangan" di
     * halaman Riwayat punya lebih dari satu titik dan benar-benar membentuk
     * garis. Semua tanggal dihitung relatif terhadap hari seeding, jadi
     * sebarannya tetap sama kapan pun seeder dijalankan.
     */
    public function run(): void
    {
        $demo = [
            // [nama barang, jenis saran, isi saran, status, stok, hari lalu, jam lalu]
            [
                'Susu UHT 1L', 'Diskon',
                'Berikan diskon 30% untuk 18 unit Susu UHT 1L karena sisa masa simpan tinggal 4 hari agar perputaran stok tetap lancar.',
                'berisiko', 18, 6, 3,
            ],
            [
                'Roti Tawar', 'Distribusi',
                'Distribusikan 15 unit Roti Tawar ke mitra dapur umum hari ini karena sisa masa simpan tinggal 2 hari dan stok belum bergerak.',
                'kritis', 15, 4, 5,
            ],
            [
                'Tomat Segar', 'Dibuang',
                'Segera lakukan pembuangan 8 unit Tomat Segar yang telah melewati masa kadaluarsa demi menjaga keselamatan konsumen dan kebersihan gudang.',
                'kritis', 8, 3, 2,
            ],
            [
                'Yogurt Cup', 'Bundling',
                'Gabungkan 20 unit Yogurt Cup dengan produk roti menjadi paket sarapan hemat agar lebih menarik dan cepat terjual sebelum kadaluarsa.',
                'berisiko', 20, 3, 1,
            ],
            [
                'Pisang Cavendish', 'Diskon',
                'Terapkan diskon kilat hingga 50% untuk 22 unit Pisang Cavendish agar segera terjual habis sebelum masa simpan berakhir.',
                'kritis', 22, 1, 6,
            ],
            [
                'Keju Slice', 'Diskon',
                'Berikan diskon 50% sampai 70% untuk 10 unit Keju Slice karena sisa masa simpan tinggal 1 hari agar segera habis terjual.',
                'kritis', 10, 0, 4,
            ],
            [
                'Selada Segar', 'Dibuang',
                'Segera lakukan pembuangan 5 unit Selada Segar yang telah melewati masa kadaluarsa selama 1 hari demi menjaga keselamatan konsumen dan kebersihan gudang.',
                'kritis', 5, 0, 1,
            ],
        ];

        foreach ($demo as [$namaBarang, $jenis, $isi, $status, $stok, $hariLalu, $jamLalu]) {
            $item = Item::where('nama', $namaBarang)->first();

            if (! $item) {
                $this->command?->warn("RekomendasiDemoSeeder: barang \"{$namaBarang}\" tidak ditemukan, dilewati.");

                continue;
            }

            $waktu = Carbon::now()->subDays($hariLalu)->subHours($jamLalu);

            Rekomendasi::create([
                'item_id' => $item->id,
                'jenis_saran' => $jenis,
                'isi_saran' => $isi,
                'status_item_saat_dibuat' => $status,
                'jumlah_stok_saat_dibuat' => $stok,
                'diterapkan' => true,
                'diterapkan_at' => $waktu,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
        }
    }
}
