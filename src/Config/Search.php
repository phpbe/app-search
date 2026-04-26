<?php
namespace Be\App\Search\Config;

/**
 * @BeConfig("搜索")
 */
class Search
{

    /**
     * @BeConfigItem("默认分页条数", driver="FormItemInputNumberInt")
     */
    public int $pageSize = 15;

    /**
     * @BeConfigItem("搜索记录数（条）",
     *     description="用于热门搜索等",
     *     driver="FormItemInputNumberInt",
     *     ui="return [':min' => 1];"
     * )
     */
    public int $searchHistory = 1000;

}
