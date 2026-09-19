<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum upload size
    |--------------------------------------------------------------------------
    |
    | In kilobytes, as expected by Laravel's "max" validation rule for files.
    | 10 MB per the Sprint 1 spec.
    |
    */

    'max_size_kb' => 10240,

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME types
    |--------------------------------------------------------------------------
    |
    | Checked against the file's actual content (via the "mimetypes" rule),
    | never the client-supplied extension.
    |
    */

    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ],

];
