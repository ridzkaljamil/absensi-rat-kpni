<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pager extends BaseConfig
{
    public array $templates = [
        'default_full'   => 'CodeIgniter\Pager\Views\default_full',
        'default_simple' => 'CodeIgniter\Pager\Views\default_simple',
        'kpni_pager'       => 'App\Views\_partials\kpni_pager',
    ];

    public int $perPage        = 10;
    public int $defaultPerPage = 10;
    public int $surroundCount  = 2;
}
