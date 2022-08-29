<?php

return [
    'licenses' => [
        [
            'id' => 1,
            'users_limit' => 10,
            'workspaces_limit' => 3,
            'storage_limit' => 100,
            'file_size_limit' => 2,
            'mappers' => [
                [
                    'chargify_website_id' => 'ic_test',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => [
                        '115708'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'ic_eur',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => [
                        '115734'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'ic_sek',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => [
                        '115922',
                        '161257'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'bimc_sek',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => [
                        '116015',
                        '161254'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'ic_usd',
                    'chargify_product_handle' => 'basic-*',
                    'chargify_users_component_ids' => [
                        '115949'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ]
            ],
        ],
        [
            'id' => 2,
            'users_limit' => null,
            'workspaces_limit' => 50,
            'storage_limit' => 1000,
            'file_size_limit' => 5,
            'mappers' => [
                [
                    'chargify_website_id' => 'ic_eur',
                    'chargify_product_handle' => 'business-*',
                    'chargify_users_component_ids' => [
                        '115737'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'ic_sek',
                    'chargify_product_handle' => 'business-*',
                    'chargify_users_component_ids' => [
                        '115925',
                        '161260'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'ic_usd',
                    'chargify_product_handle' => 'business-*',
                    'chargify_users_component_ids' => [
                        '115952'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'bimc_eur',
                    'chargify_product_handle' => 'business-*',
                    'chargify_users_component_ids' => [
                        '115897'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'bimc_usd',
                    'chargify_product_handle' => 'business-*',
                    'chargify_users_component_ids' => [
                        '115997'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ],
                [
                    'chargify_website_id' => 'bimc_sek',
                    'chargify_product_handle' => 'business-*',
                    'chargify_users_component_ids' => [
                        '116015',
                        '161255'
                    ],
                    'chargify_workspaces_component_ids' => null,
                    'chargify_storage_component_ids' => null
                ]
            ],
        ],
        [
            'id' => 3,
            'users_limit' => null,
            'workspaces_limit' => null,
            'storage_limit' => null,
            'file_size_limit' => 5,
            'mappers' => [
                [
                    'chargify_website_id' => 'ic_eur',
                    'chargify_product_handle' => 'enterprise-*',
                    'chargify_users_component_ids' => [
                        '115756',
                        '115741',
                        '115914',
                        '115762'
                    ],
                    'chargify_workspaces_component_ids' => [
                        '115757',
                        '115747',
                        '115915',
                        '115767'
                    ],
                    'chargify_storage_component_ids' => [
                        '115755',
                        '115740',
                        '115913',
                        '115768'
                    ]
                ],
                [
                    'chargify_website_id' => 'ic_sek',
                    'chargify_product_handle' => 'enterprise-*',
                    'chargify_users_component_ids' => [
                        '115927',
                        '115930',
                        '115933',
                        '115935',
                        '115939',
                        '115942',
                        '115945'
                    ],
                    'chargify_workspaces_component_ids' => [
                        '115928',
                        '115931',
                        '115934',
                        '115936',
                        '115940',
                        '115943',
                        '115946'
                    ],
                    'chargify_storage_component_ids' => [
                        '115926',
                        '115929',
                        '115932',
                        '115937',
                        '115938',
                        '115941',
                        '115944'
                    ]
                ],
                [
                    'chargify_website_id' => 'ic_usd',
                    'chargify_product_handle' => 'enterprise-*',
                    'chargify_users_component_ids' => [
                        '115954',
                        '115957',
                        '115960',
                        '115962',
                        '115966',
                        '115969',
                        '115972'
                    ],
                    'chargify_workspaces_component_ids' => [
                        '115955',
                        '115958',
                        '115961',
                        '115963',
                        '115967',
                        '115970',
                        '115973'
                    ],
                    'chargify_storage_component_ids' => [
                        '115953',
                        '115956',
                        '115959',
                        '115964',
                        '115965',
                        '115968',
                        '115971'
                    ]
                ],
                [
                    'chargify_website_id' => 'bimc_eur',
                    'chargify_product_handle' => 'enterprise-*',
                    'chargify_users_component_ids' => [
                        '115902',
                        '115907',
                        '115911'
                    ],
                    'chargify_workspaces_component_ids' => [
                        '115903',
                        '115908',
                        '115912'
                    ],
                    'chargify_storage_component_ids' => [
                        '115901',
                        '115909',
                        '115910'
                    ]
                ],
                [
                    'chargify_website_id' => 'bimc_usd',
                    'chargify_product_handle' => 'enterprise-*',
                    'chargify_users_component_ids' => [
                        '116002',
                        '116007',
                        '116011'
                    ],
                    'chargify_workspaces_component_ids' => [
                        '116003',
                        '116008',
                        '116012'
                    ],
                    'chargify_storage_component_ids' => [
                        '116001',
                        '116009',
                        '116010'
                    ]
                ],
                [
                    'chargify_website_id' => 'bimc_sek',
                    'chargify_product_handle' => 'enterprise-*',
                    'chargify_users_component_ids' => [
                        '116020',
                        '116025',
                        '116029'
                    ],
                    'chargify_workspaces_component_ids' => [
                        '116021',
                        '116026',
                        '116030'
                    ],
                    'chargify_storage_component_ids' => [
                        '116019',
                        '116027',
                        '116028'
                    ]
                ]
            ],
        ],
    ]
];
