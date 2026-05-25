<?php

namespace Database\Seeders;

// Import Model User dan Kelas
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Akun ADMIN
        User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@sobat.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 2. Buat Akun TUTOR
        User::create([
            'name' => 'Budi Tutor Matematika',
            'email' => 'tutor1@sobat.com',
            'password' => Hash::make('password123'),
            'role' => 'tutor',
            'keahlian' => 'Matematika, Fisika',
            'harga_per_jam' => 75000,
        ]);

        User::create([
            'name' => 'Siti Tutor Bahasa',
            'email' => 'tutor2@sobat.com',
            'password' => Hash::make('password123'),
            'role' => 'tutor',
            'keahlian' => 'Bahasa Inggris, Arab',
            'harga_per_jam' => 60000,
        ]);

        // 3. Buat Akun SISWA
        User::create([
            'name' => 'Andi Siswa SMA',
            'email' => 'siswa1@sobat.com',
            'password' => Hash::make('password123'),
            'role' => 'siswa',
        ]);

        User::create([
            'name' => 'Dewi Siswa SMP',
            'email' => 'siswa2@sobat.com',
            'password' => Hash::make('password123'),
            'role' => 'siswa',
        ]);

        // 4. Buat Data KELAS (Mata Pelajaran)
        Kelas::create([
            'nama_kelas' => 'Matematika Dasar',
            'deskripsi' => 'Belajar aljabar dan geometri dasar',
            'tingkat' => 'SMP',
            'harga_per_sesi' => 50000,
        ]);

        Kelas::create([
            'nama_kelas' => 'Fisika Lanjutan',
            'deskripsi' => 'Mekanika dan termodinamika',
            'tingkat' => 'SMA',
            'harga_per_sesi' => 80000,
        ]);

        Kelas::create([
            'nama_kelas' => 'Bahasa Inggris Conversation',
            'deskripsi' => 'Belajar speaking dan listening',
            'tingkat' => 'SMA',
            'harga_per_sesi' => 60000,
        ]);
    }
}
