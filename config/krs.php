<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KRS Rules
    |--------------------------------------------------------------------------
    |
    | The maximum number of credits a student may enroll in during one KRS
    | period. Override this value with KRS_MAX_CREDITS in the environment.
    |
    */

    'max_credits' => (int) env('KRS_MAX_CREDITS', 24),
];
