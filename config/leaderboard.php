<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Leaderboard Store
    |--------------------------------------------------------------------------
    |
    | Scores are intentionally stored in a JSON file so the arcade can run
    | without a database. The file is created automatically on the first
    | submitted result. It must live somewhere writable by the web server.
    |
    */

    'path' => env('LEADERBOARD_PATH', storage_path('app/leaderboard.json')),

];
