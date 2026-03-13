<?php

return [
    'limits_mb' => [
        'image' => (int) env('MEDIA_MAX_IMAGE_MB', 100),
        'audio' => (int) env('MEDIA_MAX_AUDIO_MB', 500),
        'video' => (int) env('MEDIA_MAX_VIDEO_MB', 2048),
    ],
    'limits_kb' => [
        'image' => ((int) env('MEDIA_MAX_IMAGE_MB', 100)) * 1024,
        'audio' => ((int) env('MEDIA_MAX_AUDIO_MB', 500)) * 1024,
        'video' => ((int) env('MEDIA_MAX_VIDEO_MB', 2048)) * 1024,
    ],
    'limits_bytes' => [
        'image' => ((int) env('MEDIA_MAX_IMAGE_MB', 100)) * 1024 * 1024,
        'audio' => ((int) env('MEDIA_MAX_AUDIO_MB', 500)) * 1024 * 1024,
        'video' => ((int) env('MEDIA_MAX_VIDEO_MB', 2048)) * 1024 * 1024,
    ],
];
