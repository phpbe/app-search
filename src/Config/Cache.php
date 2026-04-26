<?php
namespace Be\App\Search\Config;

/**
 * @BeConfig("缓存")
 */
class Cache
{

    /**
     * @BeConfigItem("搜索数据详情", driver="FormItemInputNumberInt")
     */
    public int $searchItem = 600;

    /**
     * @BeConfigItem("搜索结果列表", driver="FormItemInputNumberInt")
     */
    public int $searchItems = 600;

    /**
     * @BeConfigItem("热搜关键词", driver="FormItemInputNumberInt")
     */
    public int $hotKeywords = 600;

}
