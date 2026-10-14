<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@simple-krs.test'],
            ['name' => 'Admin Demo', 'password' => 'password', 'role' => 'admin'],
        );

        User::updateOrCreate(
            ['email' => 'student@simple-krs.test'],
            ['name' => 'Mahasiswa Demo', 'password' => 'password', 'role' => 'student'],
        );

        // Dedicated accounts for concurrent-request and multi-user testing.
        for ($i = 2; $i <= 10; $i++) {
            User::updateOrCreate(
                ['email' => sprintf('student%02d@simple-krs.test', $i)],
                [
                    'name' => sprintf('Mahasiswa Demo %02d', $i),
                    'password' => 'password',
                    'role' => 'student',
                ],
            );
        }

        // The schedule intentionally includes both conflict and non-conflict pairs.
        // IF399 has a small quota for testing capacity races with multiple students.
        $courses = [
            ['course_code' => 'IF101', 'title' => 'Pengantar Informatika', 'lecturer' => 'Dr. Andi', 'credits' => 3, 'day_of_week' => 'Senin', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 40],
            ['course_code' => 'IF102', 'title' => 'Logika Informatika', 'lecturer' => 'Dr. Bima', 'credits' => 2, 'day_of_week' => 'Senin', 'starts_at' => '10:30', 'ends_at' => '12:00', 'capacity' => 35],
            ['course_code' => 'IF103', 'title' => 'Matematika Diskrit', 'lecturer' => 'Dra. Citra', 'credits' => 3, 'day_of_week' => 'Senin', 'starts_at' => '13:00', 'ends_at' => '15:30', 'capacity' => 35],
            ['course_code' => 'IF201', 'title' => 'Struktur Data', 'lecturer' => 'Ir. Dedi', 'credits' => 3, 'day_of_week' => 'Selasa', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 30],
            ['course_code' => 'IF202', 'title' => 'Pemrograman Web', 'lecturer' => 'Ir. Eka', 'credits' => 3, 'day_of_week' => 'Selasa', 'starts_at' => '10:00', 'ends_at' => '12:30', 'capacity' => 30],
            ['course_code' => 'IF203', 'title' => 'Basis Data', 'lecturer' => 'Dr. Fajar', 'credits' => 3, 'day_of_week' => 'Selasa', 'starts_at' => '13:00', 'ends_at' => '15:30', 'capacity' => 30],
            ['course_code' => 'IF204', 'title' => 'Jaringan Komputer', 'lecturer' => 'Dr. Gita', 'credits' => 2, 'day_of_week' => 'Rabu', 'starts_at' => '08:00', 'ends_at' => '10:00', 'capacity' => 32],
            ['course_code' => 'IF205', 'title' => 'Sistem Operasi', 'lecturer' => 'Ir. Hadi', 'credits' => 3, 'day_of_week' => 'Rabu', 'starts_at' => '10:00', 'ends_at' => '12:30', 'capacity' => 32],
            ['course_code' => 'IF206', 'title' => 'Interaksi Manusia dan Komputer', 'lecturer' => 'Dra. Intan', 'credits' => 2, 'day_of_week' => 'Rabu', 'starts_at' => '13:00', 'ends_at' => '15:00', 'capacity' => 28],
            ['course_code' => 'IF301', 'title' => 'Rekayasa Perangkat Lunak', 'lecturer' => 'Dr. Joko', 'credits' => 3, 'day_of_week' => 'Kamis', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 30],
            ['course_code' => 'IF302', 'title' => 'Keamanan Informasi', 'lecturer' => 'Dr. Karin', 'credits' => 3, 'day_of_week' => 'Kamis', 'starts_at' => '10:30', 'ends_at' => '13:00', 'capacity' => 25],
            ['course_code' => 'IF303', 'title' => 'Kecerdasan Buatan', 'lecturer' => 'Dr. Lukman', 'credits' => 3, 'day_of_week' => 'Kamis', 'starts_at' => '13:00', 'ends_at' => '15:30', 'capacity' => 25],
            ['course_code' => 'IF304', 'title' => 'Pemrograman Mobile', 'lecturer' => 'Ir. Maya', 'credits' => 3, 'day_of_week' => 'Jumat', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 28],
            ['course_code' => 'IF305', 'title' => 'Komputasi Awan', 'lecturer' => 'Dr. Nanda', 'credits' => 3, 'day_of_week' => 'Jumat', 'starts_at' => '10:30', 'ends_at' => '13:00', 'capacity' => 25],
            ['course_code' => 'IF306', 'title' => 'Sistem Terdistribusi', 'lecturer' => 'Dr. Oki', 'credits' => 3, 'day_of_week' => 'Jumat', 'starts_at' => '13:00', 'ends_at' => '15:30', 'capacity' => 25],
            ['course_code' => 'IF307', 'title' => 'Analisis Algoritma', 'lecturer' => 'Dr. Putri', 'credits' => 2, 'day_of_week' => 'Senin', 'starts_at' => '15:30', 'ends_at' => '17:00', 'capacity' => 35],
            ['course_code' => 'IF308', 'title' => 'Etika Profesi', 'lecturer' => 'Dra. Qori', 'credits' => 2, 'day_of_week' => 'Selasa', 'starts_at' => '15:30', 'ends_at' => '17:00', 'capacity' => 35],
            ['course_code' => 'IF309', 'title' => 'Data Mining', 'lecturer' => 'Dr. Raka', 'credits' => 3, 'day_of_week' => 'Rabu', 'starts_at' => '15:00', 'ends_at' => '17:30', 'capacity' => 24],
            ['course_code' => 'IF310', 'title' => 'Machine Learning', 'lecturer' => 'Dr. Sari', 'credits' => 3, 'day_of_week' => 'Kamis', 'starts_at' => '15:30', 'ends_at' => '18:00', 'capacity' => 24],
            ['course_code' => 'IF399', 'title' => 'Praktikum Uji Kuota', 'lecturer' => 'Laboratorium Informatika', 'credits' => 1, 'day_of_week' => 'Jumat', 'starts_at' => '15:30', 'ends_at' => '17:00', 'capacity' => 2],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['course_code' => $course['course_code']],
                $course,
            );
        }
    }
}
