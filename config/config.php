<?php
// Konfigurasi aplikasi
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'filkompanion',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'nama'          => 'FILKOMpanion',
        'tagline'       => 'Temani Aktivitas Kampusmu.',
        'timezone'      => 'Asia/Jakarta',
        // Domain email UB
        'domainUB'      => ['ub.ac.id', 'student.ub.ac.id'],
        // Batas unggah (MB)
        'batasUnggahMB' => 5,
        // Batas pembatalan (jam)
        'batasBatalJam' => 24,
    ],
];
