<?php
    return [
        'type'                          => "virtualization",
        'access-hash'                   => false,
        'server-info-checker'           => true,
        'server-info-port'              => true,
        'server-info-not-secure-port'   => 1020,
        'server-info-secure-port'       => 1080,
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