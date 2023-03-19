<?php
    return [
        'type'                          => "virtualization",
        'access-hash'                   => false,
        'server-info-checker'           => true,
        'server-info-port'              => true,
        'server-info-not-secure-port'   => 80,
        'server-info-secure-port'       => 443,
        'configurable-option-params'    => [
            'Disk Space',
            'Ram',
            'Swap',
            'DiskIO',
            'CPU',
            'Location-ID',
            'Egg-ID',
            'Nest-ID',
            'portrange',
            'image',
            'startup',
            'databases',
            'backup',
        ],
    ];