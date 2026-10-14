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
            [
                'name' => 'Admin Demo',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'student@simple-krs.test'],
            [
                'name' => 'Mahasiswa Demo',
                'password' => 'password',
                'role' => 'student',
            ],
        );

        $courses = [
            ['course_code' => 'IF101', 'title' => 'Pengantar Informatika', 'lecturer' => 'Dr. Andi', 'credits' => 3, 'day_of_week' => 'Senin', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 40],
            ['course_code' => 'IF203', 'title' => 'Struktur Data', 'lecturer' => 'Ir. Budi', 'credits' => 3, 'day_of_week' => 'Senin', 'starts_at' => '13:00', 'ends_at' => '15:30', 'capacity' => 35],
            ['course_code' => 'IF205', 'title' => 'Basis Data', 'lecturer' => 'Dra. Citra', 'credits' => 3, 'day_of_week' => 'Selasa', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 30],
            ['course_code' => 'IF210', 'title' => 'Sistem Operasi', 'lecturer' => 'Dr. Dedi', 'credits' => 3, 'day_of_week' => 'Rabu', 'starts_at' => '10:00', 'ends_at' => '12:30', 'capacity' => 32],
            ['course_code' => 'IF301', 'title' => 'Sistem Terdistribusi', 'lecturer' => 'Dr. Eka', 'credits' => 3, 'day_of_week' => 'Kamis', 'starts_at' => '08:00', 'ends_at' => '10:30', 'capacity' => 25],
            ['course_code' => 'IF315', 'title' => 'Rekayasa Perangkat Lunak', 'lecturer' => 'Ir. Fajar', 'credits' => 3, 'day_of_week' => 'Jumat', 'starts_at' => '13:00', 'ends_at' => '15:30', 'capacity' => 40],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['course_code' => $course['course_code']],
                $course,
            );
        }
    }
}
