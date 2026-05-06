<?php

return [
    'api_key'       => env('NFSE_API_KEY', 'gn_76A6dkb1FHZvgUEYXvvtItxQwqxp8p0zKCQtBugAdKcuyBGQI6uplSjyPJY3'),
    'base_url'      => 'https://nfe.geranet.net/api/v1',
    'ambiente'      => env('NFSE_ENV', 'homologacao') === 'homologacao' ? '2' : '1',
    'cert_path'     => env('NFSE_CERT_PATH', 'storage/app/certs/homologacao.pfx'),
    'cert_password' => env('NFSE_CERT_PASSWORD'),
    'log_channel'   => 'stack',
];
