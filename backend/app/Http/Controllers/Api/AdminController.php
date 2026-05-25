<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Pesanan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Hitung data real dari database
        $stats = [
            'total_siswa' => User::where('role', 'siswa')->count(),
            'total_tutor' => User::where('role', 'tutor')->count(),
            'total_kelas' => Kelas::count(),
            'pesanan_aktif' => Pesanan::whereIn('status', ['pending', 'diterima', 'proses'])->count(),
            'pendapatan_bulan_ini' => Transaksi::where('status_pembayaran', 'lunas')
                ->whereMonth('created_at', now()->month)
                ->sum('jumlah'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'stats' => $stats
            ]
        ]);
    }

    public function dataSiswa()
    {
        $siswa = User::where('role', 'siswa')
            ->select('id', 'name', 'email', 'phone', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $siswa
        ]);
    }

    public function dataTutor()
    {
        $tutor = User::where('role', 'tutor')
            ->select('id', 'name', 'email', 'keahlian', 'harga_per_jam', 'status')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tutor
        ]);
    }

    public function tambahTutor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'nullable|string',
            'keahlian' => 'nullable|string',
            'harga_per_jam' => 'required|numeric|min:0',
            'bio' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        $tutor = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'tutor',
            'phone' => $validated['phone'],
            'keahlian' => $validated['keahlian'],
            'harga_per_jam' => $validated['harga_per_jam'],
            'bio' => $validated['bio'],
            'status' => $validated['status']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor berhasil ditambahkan',
            'data' => $tutor
        ], 201);
    }

    // Update Tutor
    public function updateTutor(Request $request, $id)
    {
        $tutor = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'phone' => 'nullable|string',
            'keahlian' => 'nullable|string',
            'harga_per_jam' => 'required|numeric|min:0',
            'bio' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'keahlian' => $validated['keahlian'],
            'harga_per_jam' => $validated['harga_per_jam'],
            'bio' => $validated['bio'],
            'status' => $validated['status']
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $tutor->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Data tutor berhasil diperbarui',
            'data' => $tutor
        ]);
    }

    // Hapus Tutor
    public function hapusTutor($id)
    {
        $tutor = User::findOrFail($id);
        $tutor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor berhasil dihapus'
        ]);
    }

    public function dataKelas()
    {
        $kelas = Kelas::select('id', 'nama_kelas', 'tingkat', 'harga_per_sesi', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $kelas
        ]);
    }

    public function tambahKelas(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'tingkat' => 'required|in:SD,SMP,SMA',
            'harga_per_sesi' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string'
        ]);

        $kelas = Kelas::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil ditambahkan',
            'data' => $kelas
        ], 201);
    }

    public function chartPendapatan()
    {
        $data = Transaksi::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as bulan,
                SUM(CASE WHEN status_pembayaran = "lunas" THEN jumlah ELSE 0 END) as pendapatan,
                SUM(CASE WHEN status_pembayaran != "lunas" THEN jumlah ELSE 0 END) as pengeluaran
            ')
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->limit(6)
            ->get()
            ->reverse(); // Urutkan dari terlama ke terbaru

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function laporanTerbaru()
    {
        $laporan = Pesanan::with(['siswa', 'tutor', 'kelas'])
            ->select('id', 'siswa_id', 'tutor_id', 'kelas_id', 'tanggal', 'status', 'created_at')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $laporan
        ]);
    }

    public function getLaporan(Request $request)
    {
        $query = \App\Models\Laporan::with('pelapor:id,name,email');

        if ($request->status) $query->where('status', $request->status);
        if ($request->kategori) $query->where('kategori', $request->kategori);
        if ($request->date) $query->whereDate('created_at', $request->date);

        $laporan = $query->latest()->get();

        return response()->json(['status' => 'success', 'data' => $laporan]);
    }

    // Update Status Laporan
    public function updateStatusLaporan($id, Request $request)
    {
        $laporan = \App\Models\Laporan::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,proses,selesai,ditolak']);

        $laporan->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'data' => $laporan]);
    }

    // Delete Laporan
    public function deleteLaporan($id)
    {
        \App\Models\Laporan::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Laporan dihapus']);
    }

    public function getTransaksi(Request $request)
    {
        $query = \App\Models\Transaksi::with(['pesanan.kode', 'siswa:id,name', 'tutor:id,name', 'pesanan.kelas:id,nama_kelas']);

        if ($request->status) $query->where('status_pembayaran', $request->status);
        if ($request->metode) $query->where('metode_pembayaran', $request->metode);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);

        $transaksi = $query->latest()->get();

        // Hitung total pendapatan (hanya yang lunas)
        $totalPendapatan = \App\Models\Transaksi::where('status_pembayaran', 'lunas')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->sum('jumlah');

        return response()->json([
            'status' => 'success',
            'data' => $transaksi,
            'total_pendapatan' => $totalPendapatan
        ]);
    }

    // Update Status Transaksi
    public function updateStatusTransaksi($id, Request $request)
    {
        $transaksi = \App\Models\Transaksi::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,lunas,gagal,refund']);

        $transaksi->update(['status_pembayaran' => $request->status]);

        // Jika lunas, update pesanan jadi 'proses'
        if ($request->status === 'lunas' && $transaksi->pesanan) {
            $transaksi->pesanan->update(['status' => 'proses']);
        }

        return response()->json(['status' => 'success', 'data' => $transaksi]);
    }

    // Export Transaksi (CSV)
    public function exportTransaksi(Request $request)
    {
        // Implementasi export ke CSV/Excel
        // Return file download atau JSON dengan data untuk client-side export
        $data = $this->getTransaksi($request)->original['data'];

        return response()->json(['status' => 'success', 'data' => $data]);
    }
}
