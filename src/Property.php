<?php

namespace Be\App\Search;


class Property extends \Be\App\Property
{

    protected string $label = '搜索';
    protected string $icon = 'el-icon-search';
    protected string $description = '全站搜索';

    public function __construct() {
        parent::__construct(__FILE__);
    }

}
