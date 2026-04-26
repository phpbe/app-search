<?php

namespace Be\App\Search\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class SearchItem
{

    /**
     * 获取应用键值对
     *
     * @return array
     */
    public function getAppKeyValues(): array
    {
        $db = Be::getDb();
        $sql = 'SELECT name, label FROM search_app WHERE is_delete=0 AND is_enable=1 ORDER BY ordering ASC';
        $rows = $db->getObjects($sql);

        $keyValues = [];
        foreach ($rows as $row) {
            $keyValues[$row->name] = $row->label;
        }
        return $keyValues;
    }

}
