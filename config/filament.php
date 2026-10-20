<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | All Filament file uploads (FileUpload, ImageColumn, RichEditor
    | attachments, etc.) are stored on this disk unless a component
    | explicitly calls ->disk(...). Admin uploads must be web-accessible,
    | so the "public" disk is used by default.
    |
    */

    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

];
