<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Materi;
use App\Models\Pesanan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TutorController extends Controller
{
    // ===== DASHBOARD =====

    /**
     * Dashboard tutor: stats & info user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $pesananMasuk = Pesanan::where('tutor_id', $user->id)->where('status', 'pending')->count();
        $pesananAktif = Pesanan::where('tutor_id', $user->id)->whereIn('status', ['diterima', 'proses'])->count();
        $totalSiswa = Pesanan::where('tutor_id', $user->id)->where('status', 'selesai')->distinct('siswa_id')->count('siswa_id');
        $ratingRataRata = $user->rating_rata_rata ?? 0;

        // Pendapatan bulan ini (dari transaksi lunas)
        $pendapatanBulanIni = Transaksi::where('tutor_id', $user->id)
            ->where('status_pembayaran', 'lunas')
            ->whereMonth('created_at', now()->month)
            ->sum('jumlah');

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'pesanan_masuk' => $pesananMasuk,
                'pesanan_aktif' => $pesananAktif,
                'total_siswa' => $totalSiswa,
                'rating_rata_rata' => $ratingRataRata,
                'pendapatan_bulan_ini' => $pendapatanBulanIni
            ]
        ]);
    }

    /**
     * Ringkasan statistik tutor
     */
    public function getRingkasan(Request $request)
    {
        $user = $request->user();

        // Stats dari 30 hari terakhir
        $startDate = now()->subDays(30);

        $pesananBaru = Pesanan::where('tutor_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $pesananSelesai = Pesanan::where('tutor_id', $user->id)
            ->where('status', 'selesai')
            ->where('updated_at', '>=', $startDate)
            ->count();

        $pendapatan = Transaksi::where('tutor_id', $user->id)
            ->where('status_pembayaran', 'lunas')
            ->where('created_at', '>=', $startDate)
            ->sum('jumlah');

        return response()->json([
            'status' => 'success',
            'data' => [
                'pesanan_baru_30hari' => $pesananBaru,
                'pesanan_selesai_30hari' => $pesananSelesai,
                'pendapatan_30hari' => $pendapatan,
                'rating' => $user->rating_rata_rata ?? 0,
                'total_review' => $user->reviews_count ?? 0
            ]
        ]);
    }

    // ===== PROFIL =====

    /**
     * Get profil tutor
     */
    public function profil(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => $user->only(['id', 'name', 'email', 'phone', 'address', 'photo', 'bio', 'keahlian', 'harga_per_jam', 'rating_rata_rata', 'created_at'])
        ]);
    }

    /**
     * Update profil tutor
     */
    public function updateProfil(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'keahlian' => 'nullable|string|max:255',
            'harga_per_jam' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'password' => 'nullable|min:6|confirmed'
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'address' => $validated['address'] ?? $user->address,
            'bio' => $validated['bio'] ?? $user->bio,
            'keahlian' => $validated['keahlian'] ?? $user->keahlian,
            'harga_per_jam' => $validated['harga_per_jam'] ?? $user->harga_per_jam,
        ];

        // Handle upload foto
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $request->file('photo')->store('photos/tutor', 'public');
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

    // ===== DAFTAR PESANAN =====

    /**
     * List pesanan tutor dengan filter
     */
    public function daftarPesanan(Request $request)
    {
        $query = Pesanan::where('tutor_id', $request->user()->id)
            ->with(['siswa:id,name,email', 'kelas:id,nama_kelas']);

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
        $pesanan = Pesanan::where('tutor_id', request()->user()->id)
            ->with(['siswa:id,name,email,phone', 'kelas:id,nama_kelas,deskripsi', 'transaksi'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $pesanan
        ]);
    }

    /**
     * Update status pesanan (diterima/ditolak/selesai)
     */
    public function updateStatusPesanan(Request $request, $id)
    {
        $pesanan = Pesanan::findOrFail($id);

        // Pastikan pesanan ini milik tutor yang sedang login
        if ($pesanan->tutor_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['status' => 'required|in:diterima,ditolak,selesai,proses']);

        $pesanan->update(['status' => $request->status]);

        // Jika diterima, update transaksi jadi pending
        if ($request->status === 'diterima') {
            \App\Models\Transaksi::updateOrCreate(
                ['pesanan_id' => $pesanan->id],
                [
                    'siswa_id' => $pesanan->siswa_id,
                    'tutor_id' => $pesanan->tutor_id,
                    'jumlah' => $pesanan->harga,
                    'status_pembayaran' => 'pending'
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status pesanan berhasil diupdate',
            'data' => $pesanan->load(['siswa:id,name', 'kelas:id,nama_kelas'])
        ]);
    }

    // ===== DAFTAR SISWA =====

    /**
     * Get daftar siswa yang diajar tutor
     */
    public function getDaftarSiswa(Request $request)
    {
        $tutorId = $request->user()->id;

        // Ambil siswa dari pesanan yang terkait dengan tutor ini
        $siswa = Pesanan::where('tutor_id', $tutorId)
            ->whereNotIn('status', ['dibatalkan'])
            ->with(['siswa:id,name,email,phone,address', 'kelas:id,nama_kelas'])
            ->select('siswa_id', 'status', 'updated_at', 'tanggal', 'jam')
            ->get()
            ->groupBy('siswa_id')
            ->map(function ($pesanan) {
                $p = $pesanan->first();
                return [
                    'id' => $p->siswa_id,
                    'name' => $p->siswa->name,
                    'email' => $p->siswa->email,
                    'phone' => $p->siswa->phone,
                    'sekolah' => $p->siswa->address,
                    'mapel' => $p->kelas?->nama_kelas,
                    'progres' => min(100, $pesanan->where('status', 'selesai')->count() * 25), // 4 sesi = 100%
                    'sesi_selesai' => $pesanan->where('status', 'selesai')->count(),
                    'sesi_total' => $pesanan->count(),
                    'last_session' => $pesanan->max('updated_at'),
                    'status_pesanan' => $pesanan->first()?->status,
                    'catatan' => $pesanan->first()?->catatan_siswa
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $siswa
        ]);
    }

    /**
     * Get detail progres siswa
     */
    public function getSiswaDetail($siswaId)
    {
        $tutorId = request()->user()->id;

        // Validasi: siswa ini memang diajar oleh tutor ini
        $exists = Pesanan::where('tutor_id', $tutorId)
            ->where('siswa_id', $siswaId)
            ->exists();

        if (!$exists) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak'], 403);
        }

        $siswa = User::find($siswaId);
        $pesanan = Pesanan::where('tutor_id', $tutorId)
            ->where('siswa_id', $siswaId)
            ->with('kelas')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $siswa->id,
                'name' => $siswa->name,
                'email' => $siswa->email,
                'phone' => $siswa->phone,
                'address' => $siswa->address,
                'mapel' => $pesanan->first()?->kelas?->nama_kelas,
                'progres' => min(100, $pesanan->where('status', 'selesai')->count() * 25),
                'sesi_selesai' => $pesanan->where('status', 'selesai')->count(),
                'sesi_total' => $pesanan->count(),
                'last_session' => $pesanan->max('updated_at'),
                'catatan' => $pesanan->first()?->catatan_siswa,
                'riwayat' => $pesanan->map(fn($p) => [
                    'id' => $p->id,
                    'tanggal' => $p->tanggal,
                    'jam' => $p->jam,
                    'status' => $p->status,
                    'catatan_tutor' => $p->catatan_tutor,
                    'catatan_siswa' => $p->catatan_siswa
                ])
            ]
        ]);
    }

    // ===== JADWAL MENGAJAR =====

    /**
     * Get jadwal mengajar tutor
     */
    public function getJadwalMengajar(Request $request)
    {
        $jadwal = Pesanan::where('tutor_id', $request->user()->id)
            ->whereIn('status', ['diterima', 'proses'])
            ->with(['siswa:id,name', 'kelas:id,nama_kelas'])
            ->select('id', 'tanggal', 'jam', 'siswa_id', 'kelas_id', 'status', 'catatan_tutor')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $jadwal
        ]);
    }

    /**
     * Update jadwal (tambah catatan/ubah waktu)
     */
    public function updateJadwal(Request $request)
    {
        $validated = $request->validate([
            'pesanan_id' => 'required|exists:pesanan,id',
            'catatan_tutor' => 'nullable|string|max:500',
            // Optional: update tanggal/jam jika perlu
            'tanggal' => 'nullable|date',
            'jam' => 'nullable|date_format:H:i'
        ]);

        $pesanan = Pesanan::where('tutor_id', $request->user()->id)
            ->findOrFail($validated['pesanan_id']);

        $updateData = ['catatan_tutor' => $validated['catatan_tutor'] ?? $pesanan->catatan_tutor];

        if (!empty($validated['tanggal'])) $updateData['tanggal'] = $validated['tanggal'];
        if (!empty($validated['jam'])) $updateData['jam'] = $validated['jam'];

        $pesanan->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Jadwal berhasil diperbarui',
            'data' => $pesanan
        ]);
    }

    // ===== MATERI PEMBELAJARAN =====

    /**
     * List materi yang diupload tutor
     */
    public function getMateri(Request $request)
    {
        $query = Materi::where('tutor_id', $request->user()->id);

        // Filter by kelas (opsional)
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        // Search by judul
        if ($request->filled('search')) {
            $query->where('judul', 'like', "%{$request->search}%");
        }

        $materi = $query->with('kelas:id,nama_kelas')
            ->latest()
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'judul' => $m->judul,
                    'deskripsi' => $m->deskripsi,
                    'file_url' => asset('storage/' . $m->file_path),
                    'file_name' => basename($m->file_path),
                    'tipe' => strtoupper(pathinfo($m->file_path, PATHINFO_EXTENSION)),
                    'file_size' => $m->file_size,
                    'created_at' => $m->created_at,
                    'kelas' => $m->kelas,
                    'views' => $m->views ?? 0,
                    'downloads' => $m->downloads ?? 0
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $materi
        ]);
    }

    /**
     * Upload materi baru
     */
    public function uploadMateri(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'kelas_id' => 'nullable|exists:kelas,id',
            'file' => 'required|file|mimes:pdf,doc,docx,pptx,ppt,jpg,jpeg,png,mp4|max:10240' // Max 10MB
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = 'materi_' . time() . '_' . uniqid() . '.' . $extension;

        // Simpan di storage/app/public/materi
        $path = $file->storeAs('materi', $fileName, 'public');

        $materi = Materi::create([
            'tutor_id' => $request->user()->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'kelas_id' => $request->kelas_id,
            'file_path' => $path,
            'tipe_file' => strtolower($extension),
            'file_size' => $file->getSize()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Materi berhasil diupload',
            'data' => $materi
        ], 201);
    }

    /**
     * Delete materi
     */
    public function deleteMateri($id)
    {
        $materi = Materi::where('tutor_id', request()->user()->id)->findOrFail($id);

        // Hapus file dari storage
        if ($materi->file_path && Storage::disk('public')->exists($materi->file_path)) {
            Storage::disk('public')->delete($materi->file_path);
        }

        $materi->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Materi berhasil dihapus'
        ]);
    }

    // ===== PENDAPATAN =====

    /**
     * Get pendapatan tutor dengan filter
     */
    public function getPendapatan(Request $request)
    {
        $tutorId = $request->user()->id;

        // Filter by date range
        $query = \App\Models\Transaksi::where('tutor_id', $tutorId)
            ->where('status_pembayaran', 'lunas');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Group by month for chart
        $groupBy = $request->input('group_by', 'month');

        if ($groupBy === 'month') {
            $data = $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bulan, SUM(jumlah) as total, COUNT(*) as count')
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get();
        } else {
            $data = $query->selectRaw('DATE(created_at) as tanggal, SUM(jumlah) as total, COUNT(*) as count')
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get();
        }

        // Stats
        $totalAllTime = \App\Models\Transaksi::where('tutor_id', $tutorId)
            ->where('status_pembayaran', 'lunas')
            ->sum('jumlah');

        $saldo = $totalAllTime - \App\Models\Withdraw::where('tutor_id', $tutorId)
            ->whereIn('status', ['success', 'pending'])
            ->sum('nominal');

        $prosesPenarikan = \App\Models\Withdraw::where('tutor_id', $tutorId)
            ->where('status', 'pending')
            ->sum('nominal');

        // Riwayat transaksi (income + withdrawal)
        $riwayat = collect();

        // Income transactions
        $income = \App\Models\Transaksi::where('tutor_id', $tutorId)
            ->where('status_pembayaran', 'lunas')
            ->with(['pesanan.siswa', 'pesanan.kelas'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'tipe' => 'income',
                'deskripsi' => 'Sesi ' . ($t->pesanan?->kelas?->nama_kelas ?? 'Mata Pelajaran') . ' - ' . ($t->pesanan?->siswa?->name ?? 'Siswa'),
                'jumlah' => $t->jumlah,
                'tanggal' => $t->created_at,
                'status' => 'success'
            ]);

        // Withdrawal transactions
        $withdrawals = \App\Models\Withdraw::where('tutor_id', $tutorId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'tipe' => 'withdrawal',
                'deskripsi' => 'Penarikan Saldo (' . strtoupper($w->bank) . ')',
                'jumlah' => -$w->nominal,
                'tanggal' => $w->created_at,
                'status' => $w->status
            ]);

        // Merge & sort by date
        $riwayat = $income->merge($withdrawals)
            ->sortByDesc('tanggal')
            ->values()
            ->take(10);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'saldo' => max(0, $saldo),
            'total_all_time' => $totalAllTime,
            'proses_penarikan' => $prosesPenarikan,
            'riwayat' => $riwayat
        ]);
    }

    /**
     * Request withdrawal
     */
    public function requestWithdraw(Request $request)
    {
        $tutor = $request->user();

        $validated = $request->validate([
            'bank' => 'required|in:bca,mandiri,bni,bri,dana',
            'nomor_rekening' => 'required|string|max:50',
            'nominal' => 'required|numeric|min:50000',
            'admin_fee' => 'nullable|numeric'
        ]);

        // Hitung saldo available
        $totalPendapatan = \App\Models\Transaksi::where('tutor_id', $tutor->id)
            ->where('status_pembayaran', 'lunas')
            ->sum('jumlah');

        $totalWithdrawn = \App\Models\Withdraw::where('tutor_id', $tutor->id)
            ->whereIn('status', ['success', 'pending'])
            ->sum('nominal');

        $saldoAvailable = $totalPendapatan - $totalWithdrawn;

        if ($validated['nominal'] > $saldoAvailable) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi'
            ], 400);
        }

        // Create withdrawal request
        $withdraw = \App\Models\Withdraw::create([
            'tutor_id' => $tutor->id,
            'bank' => $validated['bank'],
            'nomor_rekening' => $validated['nomor_rekening'],
            'nominal' => $validated['nominal'],
            'admin_fee' => $validated['admin_fee'] ?? 2500,
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan penarikan berhasil dikirim',
            'data' => $withdraw
        ], 201);
    }

    public function getKelas(Request $request)
    {
        $query = \App\Models\Kelas::where('tutor_id', $request->user()->id);

        // Filter by kategori/tingkat
        if ($request->filled('kategori')) {
            $query->where('tingkat', $request->kategori);
        }

        // Search by judul
        if ($request->filled('search')) {
            $query->where('nama_kelas', 'like', "%{$request->search}%");
        }

        $kelas = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $kelas
        ]);
    }

    /**
     * Create kelas baru
     */
    public function createKelas(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'tingkat' => 'required|in:SD,SMP,SMA,Umum',
            'harga_per_sesi' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string|max:500'
        ]);

        $kelas = \App\Models\Kelas::create([
            'tutor_id' => $request->user()->id,
            'nama_kelas' => $validated['nama_kelas'],
            'tingkat' => $validated['tingkat'],
            'harga_per_sesi' => $validated['harga_per_sesi'],
            'deskripsi' => $validated['deskripsi']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil ditambahkan',
            'data' => $kelas
        ], 201);
    }

    /**
     * Update kelas
     */
    public function updateKelas(Request $request, $id)
    {
        $kelas = \App\Models\Kelas::where('tutor_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'tingkat' => 'required|in:SD,SMP,SMA,Umum',
            'harga_per_sesi' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string|max:500'
        ]);

        $kelas->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil diperbarui',
            'data' => $kelas
        ]);
    }

    /**
     * Delete kelas
     */
    public function deleteKelas($id)
    {
        $kelas = \App\Models\Kelas::where('tutor_id', request()->user()->id)->findOrFail($id);
        $kelas->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dihapus'
        ]);
    }
}
