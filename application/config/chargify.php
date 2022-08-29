<?php

return [
    'chargify' => [
        'ic_eur' => [
            'api_id' => getenv('CHARGIFY_IC_EUR_API_ID'),
            'api_secret' => getenv('CHARGIFY_IC_EUR_API_SECRET'),
            'api_password' => getenv('CHARGIFY_IC_EUR_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_IC_EUR_SHARED_KEY')
        ],
        'ic_sek' => [
            'api_id' => getenv('CHARGIFY_IC_SEK_API_ID'),
            'api_secret' => getenv('CHARGIFY_IC_SEK_API_SECRET'),
            'api_password' => getenv('CHARGIFY_IC_SEK_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_IC_SEK_SHARED_KEY')
        ],
        'ic_usd' => [
            'api_id' => getenv('CHARGIFY_IC_USD_API_ID'),
            'api_secret' => getenv('CHARGIFY_IC_USD_API_SECRET'),
            'api_password' => getenv('CHARGIFY_IC_USD_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_IC_USD_SHARED_KEY')
        ],
        'ic_test' => [
            'api_id' => getenv('CHARGIFY_IC_TEST_API_ID'),
            'api_secret' => getenv('CHARGIFY_IC_TEST_API_SECRET'),
            'api_password' => getenv('CHARGIFY_IC_TEST_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_IC_TEST_SHARED_KEY')
        ],
        'bimc_eur' => [
            'api_id' => getenv('CHARGIFY_BIMC_EUR_API_ID'),
            'api_secret' => getenv('CHARGIFY_BIMC_EUR_API_SECRET'),
            'api_password' => getenv('CHARGIFY_BIMC_EUR_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_BIMC_EUR_SHARED_KEY')
        ],
        'bimc_usd' => [
            'api_id' => getenv('CHARGIFY_BIMC_USD_API_ID'),
            'api_secret' => getenv('CHARGIFY_BIMC_USD_API_SECRET'),
            'api_password' => getenv('CHARGIFY_BIMC_USD_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_BIMC_USD_SHARED_KEY')
        ],
        'bimc_sek' => [
            'api_id' => getenv('CHARGIFY_BIMC_SEK_API_ID'),
            'api_secret' => getenv('CHARGIFY_BIMC_SEK_API_SECRET'),
            'api_password' => getenv('CHARGIFY_BIMC_SEK_API_PASSWORD'),
            'shared_key' => getenv('CHARGIFY_BIMC_SEK_SHARED_KEY')
        ]
    ]
];
