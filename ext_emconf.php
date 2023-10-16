<?php

// Extension Manager/Repository config file for ext "default_upload_folder"

// @phpstan-ignore-next-line
$EM_CONF[$_EXTKEY] = [
    'title' => 'Default upload folder',
    'description' => 'Make it possible to configure the default upload folder for a certain TCA column',
    'category' => 'be',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Frans Saris',
    'author_email' => 't3ext@beecht.it',
    'author_company' => 'Beech.it',
    'version' => '2.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'clearcacheonload' => true,
];
