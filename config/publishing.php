<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Approval Score
    |--------------------------------------------------------------------------
    | Minimum AI Health Score a post's best attempt must reach before it can be
    | published or scheduled. Overridable at runtime from /settings (DB-backed)
    | via the `approval_score` setting key.
    */
    'approval_score' => (int) env('PUBLISHING_APPROVAL_SCORE', 60),

    /*
    |--------------------------------------------------------------------------
    | Platform Map
    |--------------------------------------------------------------------------
    | Canonical scope id => platform key. Drives publish dispatch and the
    | per-platform analytics report.
    */
    'platforms' => [
        0 => 'youtube',
        1 => 'instagram',
        2 => 'facebook',
        3 => 'linkedin',
    ],

];
