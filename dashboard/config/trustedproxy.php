<?php

return [
    'proxies' => array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))),
];
