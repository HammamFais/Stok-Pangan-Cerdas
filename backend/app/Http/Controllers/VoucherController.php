<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Rekomendasi;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    /**
     * Daftar voucher. Mendukung filter ?status=aktif.
     */
    public function index(Request $request)
    {
        $vouchers = Voucher::query()
            ->with(['item', 'rekomendasi'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return response()->json([
            'data' => $vouchers,
        ]);
    }

    /**
     * Terbitkan voucher baru. Kode digenerate di backend, tidak pernah
     * dipercaya dari input frontend.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['nullable', 'exists:items,id'],
            'rekomendasi_id' => ['nullable', 'exists:rekomendasi,id'],
            'judul' => ['required', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'max:255'],
            'tipe' => ['required', 'in:persen,nominal'],
            'nilai' => ['required', 'integer', 'min:1'],
            'harga_normal' => ['nullable', 'integer', 'min:0'],
            'min_belanja' => ['nullable', 'integer', 'min:0'],
            'kuota' => ['required', 'integer', 'min:1'],
            'berlaku_sampai' => ['required', 'date'],
        ]);

        $item = $validated['item_id'] ? Item::find($validated['item_id']) : null;

        $voucher = Voucher::create([
            'kode' => $this->generateKodeUnik($item?->nama ?? 'SPC'),
            'item_id' => $item?->id,
            'rekomendasi_id' => $validated['rekomendasi_id'] ?? null,
            'nama_item' => $item?->nama,
            'kategori_item' => $item?->kategori,
            'judul' => $validated['judul'],
            'target' => $validated['target'] ?? null,
            'diskon_persen' => $validated['tipe'] === 'persen' ? $validated['nilai'] : null,
            'diskon_nominal' => $validated['tipe'] === 'nominal' ? $validated['nilai'] : null,
            'harga_normal' => $validated['harga_normal'] ?? null,
            'min_belanja' => $validated['min_belanja'] ?? 0,
            'kuota' => $validated['kuota'],
            'terpakai' => 0,
            'berlaku_sampai' => $validated['berlaku_sampai'],
            'status' => 'aktif',
        ]);

        return response()->json([
            'data' => $voucher->load(['item', 'rekomendasi']),
        ], 201);
    }

    /**
     * Periksa validitas kode voucher secara berurutan: ada, aktif, belum
     * kadaluarsa, kuota tersisa. Tidak menaikkan pemakaian — itu tugas
     * endpoint klaim.
     */
    public function validasi(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string'],
        ]);

        $kode = strtoupper(trim($validated['kode']));
        $voucher = Voucher::where('kode', $kode)->first();

        if (! $voucher) {
            return response()->json([
                'message' => "Kode voucher \"{$kode}\" tidak ditemukan.",
            ], 404);
        }

        if ($voucher->status !== 'aktif') {
            return response()->json([
                'message' => "Voucher ini berstatus \"{$voucher->status}\", tidak bisa dipakai.",
            ], 422);
        }

        if ($voucher->sudah_kadaluarsa) {
            return response()->json([
                'message' => 'Voucher ini sudah lewat masa berlaku.',
            ], 422);
        }

        if ($voucher->sisa_kuota <= 0) {
            return response()->json([
                'message' => 'Kuota voucher ini sudah habis.',
            ], 422);
        }

        return response()->json([
            'data' => $voucher->load(['item', 'rekomendasi']),
        ]);
    }

    /**
     * Klaim voucher: naikkan pemakaian setelah memeriksa ulang SEMUA syarat
     * di backend (jangan percaya validasi yang sudah dilakukan frontend).
     * Klaim yang berhasil dicatat ke Riwayat sebagai tindakan "Diskon" yang
     * sudah diterapkan, supaya statistik penyelamatan ikut bergerak.
     */
    public function klaim(Voucher $voucher)
    {
        if ($voucher->status !== 'aktif') {
            return response()->json([
                'message' => "Voucher ini berstatus \"{$voucher->status}\", tidak bisa diklaim.",
            ], 422);
        }

        if ($voucher->sudah_kadaluarsa) {
            return response()->json([
                'message' => 'Voucher ini sudah lewat masa berlaku.',
            ], 422);
        }

        if ($voucher->sisa_kuota <= 0) {
            return response()->json([
                'message' => 'Kuota voucher ini sudah habis.',
            ], 422);
        }

        $voucher->terpakai += 1;
        if ($voucher->terpakai >= $voucher->kuota) {
            $voucher->status = 'habis';
        }
        $voucher->save();

        $namaBarang = $voucher->item?->nama ?? $voucher->nama_item ?? $voucher->judul;
        $jumlahStok = $voucher->item?->jumlah_stok ?? 1;

        Rekomendasi::create([
            'item_id' => $voucher->item_id,
            'nama_item' => $voucher->nama_item ?? $voucher->item?->nama,
            'kategori_item' => $voucher->kategori_item ?? $voucher->item?->kategori,
            'jenis_saran' => 'Diskon',
            'isi_saran' => "Voucher \"{$voucher->kode}\" ({$voucher->judul}) diklaim di kasir untuk {$namaBarang}.",
            'status_item_saat_dibuat' => $voucher->item?->status ?? 'berisiko',
            'jumlah_stok_saat_dibuat' => $jumlahStok,
            'diterapkan' => true,
            'diterapkan_at' => now(),
        ]);

        return response()->json([
            'data' => $voucher->load(['item', 'rekomendasi']),
        ]);
    }

    private function generateKodeUnik(string $namaSumber): string
    {
        $prefix = Str::upper(Str::limit(preg_replace('/[^a-zA-Z]/', '', $namaSumber), 3, '')) ?: 'SPC';
        $prefix = str_pad($prefix, 3, 'X');

        do {
            $angka = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $kode = "VCHR-{$prefix}-{$angka}";
        } while (Voucher::where('kode', $kode)->exists());

        return $kode;
    }
}
