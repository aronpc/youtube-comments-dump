<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | This value determines the directory where downloaded comments and
    | live chat messages will be saved. By default, this is set to the
    | 'output' directory in the package root.
    |
    */
    'output_directory' => env('YOUTUBE_COMMENTS_OUTPUT_DIR', storage_path('app/youtube-comments')),

    /*
    |--------------------------------------------------------------------------
    | YouTube-DL Path
    |--------------------------------------------------------------------------
    |
    | This value determines the path to the youtube-dl or yt-dlp executable.
    | By default, it assumes the executable is in the system PATH.
    |
    */
    'youtube_dl_path' => env('YOUTUBE_DL_PATH', 'yt-dlp'),

    /*
    |--------------------------------------------------------------------------
    | Command Timeout
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum time (in seconds) that the package
    | will wait for a command to complete before timing out.
    |
    */
    'command_timeout' => env('YOUTUBE_COMMENTS_TIMEOUT', 300),
];
