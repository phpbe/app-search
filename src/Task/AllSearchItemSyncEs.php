<?php
namespace Be\App\Search\Task;

use Be\Be;
use Be\Task\Task;

/**
 * 搜索数据全量同步到ES和Cache
 *
 * @BeTask("搜索数据全量同步到ES和Cache")
 */
class AllSearchItemSyncEs extends Task
{

    public function execute()
    {
        $configEs = Be::getConfig('App.Search.Es');

        $service = Be::getService('App.Search.Admin.TaskSearchItem');

        $db = Be::getDb();
        $sql = 'SELECT * FROM search_item WHERE is_enable != -1';
        $objs = $db->getYieldObjects($sql);

        $batch = [];
        $i = 0;
        foreach ($objs as $obj) {
            $batch[] = $obj;

            $i++;
            if ($i >= 100) {
                if ($configEs->enable) {
                    $service->syncEs($batch);
                }

                $service->syncCache($batch);

                $batch = [];
                $i = 0;
            }
        }

        if ($i > 0) {
            if ($configEs->enable) {
                $service->syncEs($batch);
            }

            $service->syncCache($batch);
        }
    }

}
