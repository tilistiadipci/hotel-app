<?php

return [
    'trial' => [
        'label' => 'Trial (14 Hari)',
        'description' => 'Uji coba 14 hari. Hotel otomatis dinonaktifkan setelah masa trial berakhir.',
        'duration_days' => 14,
        'max_players' => 3,
        'max_users' => 3,
    ],
    'standard' => [
        'label' => 'Standard',
        'description' => 'Untuk operasional hotel reguler, maksimal 50 player dan 10 user.',
        'duration_days' => null,
        'max_players' => 50,
        'max_users' => 10,
    ],
    'premium' => [
        'label' => 'Premium',
        'description' => 'Untuk skala besar, maksimal 200 player dan 50 user.',
        'duration_days' => null,
        'max_players' => 200,
        'max_users' => 50,
    ],
    'custom' => [
        'label' => 'Custom',
        'description' => 'Batas player, user, dan tanggal berakhir ditentukan sendiri oleh superadmin.',
        'duration_days' => null,
        'max_players' => null,
        'max_users' => null,
    ],
];
