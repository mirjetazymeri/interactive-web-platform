<?php

return [
    // KiB, independent of the existing 5 MiB Hero image limit.
    'hero_video_max_kb' => (int) env('HERO_VIDEO_MAX_KB', 102400),
];
