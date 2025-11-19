<?php

return [
    "paths" => ["api/*", "sanctum/csrf-cookie", "docs", "docs/*"],
    "allowed_methods" => ["*"],
    "allowed_origins" => [
        "http://localhost",
        "http://localhost:8000",
        "http://127.0.0.1",
        'http://127.0.0.1:8000',
        "http://139.185.51.230",
        "http://139.185.51.230:80",
        "https://139.185.51.230",
    ],
    "allowed_origins_patterns" => [],
    "allowed_headers" => ["*"],
    "exposed_headers" => [],
    "max_age" => 0,
    "supports_credentials" => true,
];
