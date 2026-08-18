<?php

return [
    'enabled' => env('IMAP_ENABLED', true),
    'host' => env('IMAP_HOST', env('MAIL_HOST')),
    'port' => (int) env('IMAP_PORT', 993),
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    'validate_cert' => env('IMAP_VALIDATE_CERT', true),
    'username' => env('IMAP_USERNAME', env('MAIL_USERNAME')),
    'password' => env('IMAP_PASSWORD', env('MAIL_PASSWORD')),
    'folder' => env('IMAP_FOLDER', 'INBOX'),
    'days' => (int) env('IMAP_SYNC_DAYS', 14),
];
