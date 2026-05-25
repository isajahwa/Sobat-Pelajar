<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    // ===== DASHBOARD =====

    /**
     * Dashboard siswa: stats & info user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $pesananAktif = Pesanan::where('siswa_id', $user->id)
            ->whereIn('status', ['pending', 'diterima', 'proses'])
            ->count();

        $pesananSelesai = Pesanan::where('siswa_id', $user->id)
            ->where('status', 'selesai')
            ->count();

        $totalPengeluaran = Pesanan::where('siswa_id', $user->id)
            ->whereHas('transaksi', fn($q) => $q->where('status_pembayaran', 'lunas'))
            ->sum('harga'); // Pastikan tabel pesanan punya kolom 'harga'

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'pesanan_aktif' => $pesananAktif,
                'pesanan_selesai' => $pesananSelesai,
                'total_pengeluaran' => $totalPengeluaran
            ]
        ]);
    }

    // ===== PROFIL =====

    /**
     * Get profil siswa
     */
    public function profil(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => $user->only(['id', 'name', 'email', 'phone', 'address', 'photo', 'created_at'])
        ]);
    }

    /**
     * Update profil siswa
     */
    public function updateProfil(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:2048', // Maksimal 2MB
            'password' => 'nullable|min:6|confirmed'
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'address' => $validated['address'] ?? $user->address,
        ];

        // Handle upload foto
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos/siswa', 'public');
            $updateData['photo'] = $path;
        }

        // Handle update password
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()
        ]);
    }

    // ===== CARI TUTOR =====

    /**
     * List semua tutor dengan filter & search
     */
    public function cariTutor(Request $request)
    {
        $query = User::where('role', 'tutor')
            ->where('status', 'active')
            ->select('id', 'name', 'email', 'keahlian', 'harga_per_jam', 'bio', 'rating_rata_rata');

        // Filter by keahlian (search in string)
        if ($request->filled('keahlian')) {
            $query->where('keahlian', 'like', "%{$request->keahlian}%");
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Filter by harga min/max
        if ($request->filled('harga_min')) {
            $query->where('harga_per_jam', '>=', $request->harga_min);
        }
        if ($request->filled('harga_max')) {
            $query->where('harga_per_jam', '<=', $request->harga_max);
        }

        $tutors = $query->get();

        // Hitung total review per tutor (opsional, butuh model Review)
        $tutors->each(function ($tutor) {
            $tutor->total_review = \App\Models\Review::where('tutor_id', $tutor->id)->count();
        });

        return response()->json([
            'status' => 'success',
            'data' => $tutors
        ]);
    }

    /**
     * Get detail tutor by ID
     */
    public function getTutorById($id)
    {
        $tutor = User::where('role', 'tutor')
            ->where('id', $id)
            ->select('id', 'name', 'email', 'keahlian', 'harga_per_jam', 'bio', 'rating_rata_rata', 'phone')
            ->withCount(['reviewDiterima as total_review'])
            ->firstOrFail();

        // Get jadwal tutor (opsional)
        $tutor->jadwal_tersedia = \App\Models\Jadwal::where('tutor_id', $id)
            ->where('status', 'tersedia')
            ->get(['hari', 'jam_mulai', 'jam_selesai']);

        return response()->json([
            'status' => 'success',
            'data' => $tutor
        ]);
    }

    // ===== PESANAN =====

    /**
     * List pesanan siswa dengan filter
     */
    public function getPesanan(Request $request)
    {
        $query = Pesanan::where('siswa_id', $request->user()->id)
            ->with(['tutor:id,name', 'kelas:id,nama_kelas']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal', '<=', $request->date_to);
        }

        $pesanan = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $pesanan
        ]);
    }

    /**
     * Get detail pesanan by ID
     */
    public function getPesananById($id)
    {
        $pesanan = Pesanan::where('siswa_id', request()->user()->id)
            ->with(['tutor:id,name,email,phone', 'kelas:id,nama_kelas,tingkat', 'transaksi'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $pesanan
        ]);
    }

    /**
     * Buat pesanan baru
     */
    public function buatPesanan(Request $request)
    {
        $kelas = Kelas::findOrFail($request->kelas_id);

        $validated = $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'kelas_id' => 'required|exists:kelas,id',
            'tanggal' => 'required|date|after_or_equal:today',
            'jam' => 'required|date_format:H:i',
            'catatan_siswa' => 'nullable|string|max:500'
        ]);

        $pesanan = Pesanan::create([
            'siswa_id' => $request->user()->id,
            'tutor_id' => $validated['tutor_id'],
            'kelas_id' => $validated['kelas_id'],
            'tanggal' => $validated['tanggal'],
            'jam' => $validated['jam'],
            'catatan_siswa' => $validated['catatan_siswa'],
            'status' => 'pending',
            'harga' => $kelas->harga_per_sesi // Sudah pasti ada
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesanan berhasil dibuat, menunggu konfirmasi tutor.',
            'data' => $pesanan->load(['tutor:id,name', 'kelas:id,nama_kelas'])
        ], 201);
    }

    /**
     * Cancel pesanan (hanya jika status masih pending)
     */
    public function cancelPesanan($id)
    {
        $pesanan = Pesanan::where('siswa_id', request()->user()->id)->findOrFail($id);

        if (!in_array($pesanan->status, ['pending'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan tidak dapat dibatalkan karena sudah diproses'
            ], 400);
        }

        $pesanan->update(['status' => 'dibatalkan']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesanan berhasil dibatalkan',
            'data' => $pesanan
        ]);
    }

    /**
     * Riwayat pesanan (alias getPesanan dengan filter selesai)
     */
    public function riwayatPesanan(Request $request)
    {
        return $this->getPesanan($request->merge(['status' => 'selesai']));
    }

    // ===== MATERI =====

    /**
     * List materi yang bisa diakses siswa
     */
    // Get Materi Pembelajaran Siswa
    public function getMateri(Request $request)
    {
        $query = \App\Models\Materi::query()
            ->with(['tutor:id,name', 'kelas:id,nama_kelas']);

        // Filter by kelas yang diikuti siswa (opsional, butuh relasi)
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        // Filter by mapel/keahlian tutor
        if ($request->filled('mapel')) {
            $query->whereHas('kelas', function ($q) use ($request) {
                $q->where('nama_kelas', 'like', "%{$request->mapel}%");
            });
        }

        // Search by judul
        if ($request->filled('search')) {
            $query->where('judul', 'like', "%{$request->search}%");
        }

        // Filter by tipe file
        if ($request->filled('tipe')) {
            $query->where('tipe_file', $request->tipe);
        }

        $materi = $query->latest()->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'judul' => $m->judul,
                'deskripsi' => $m->deskripsi,
                'file_url' => asset('storage/' . $m->file_path),
                'tipe' => strtoupper($m->tipe_file),
                'file_size' => $m->file_size,
                'created_at' => $m->created_at,
                'sudah_dibaca' => false, // Bisa ditambah tracking di database
                'is_tugas' => $m->is_tugas ?? false,
                'deadline' => $m->deadline,
                'sudah_dikumpulkan' => false,
                'tutor' => $m->tutor,
                'kelas' => $m->kelas,
                'mapel' => $m->kelas?->nama_kelas
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $materi
        ]);
    }

    // Download Materi (tracking)
    public function downloadMateri($id)
    {
        $materi = \App\Models\Materi::findOrFail($id);

        // Track download (opsional)
        // \App\Models\DownloadLog::create([...]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'file_url' => asset('storage/' . $materi->file_path),
                'filename' => $materi->judul . '.' . $materi->tipe_file
            ]
        ]);
    }

    // Mark as Read
    public function markAsRead($id)
    {
        // Bisa tambah tracking di database: materi_reads table
        // \App\Models\MateriRead::firstOrCreate([...]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status baca diperbarui'
        ]);
    }

    // ===== JADWAL =====

    /**
     * Get jadwal belajar siswa
     */
    public function getJadwal(Request $request)
    {
        $jadwal = Pesanan::where('siswa_id', $request->user()->id)
            ->whereIn('status', ['diterima', 'proses'])
            ->with(['tutor:id,name', 'kelas:id,nama_kelas'])
            ->select('id', 'tanggal', 'jam', 'tutor_id', 'kelas_id', 'status')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $jadwal
        ]);
    }

    public function getProgress(Request $request)
    {
        // Contoh: hitung progress berdasarkan jumlah sesi selesai per kelas
        $progress = \App\Models\Pesanan::where('siswa_id', $request->user()->id)
            ->where('status', 'selesai')
            ->selectRaw('kelas_id, COUNT(*) as sesi_selesai')
            ->groupBy('kelas_id')
            ->with('kelas:id,nama_kelas')
            ->get()
            ->map(fn($item) => [
                'kelas' => $item->kelas,
                'persen' => min(100, $item->sesi_selesai * 20) // Contoh: 5 sesi = 100%
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $progress
        ]);
    }
}
