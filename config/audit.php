<?php

return [
    'enabled'        => env('AUDIT_ENABLED', true),
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
];
