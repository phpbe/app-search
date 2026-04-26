<?php
namespace Be\App\Search\Config;

/**
 * @BeConfig("ES搜索引擎")
 */
class Es
{

    /**
     * @BeConfigItem("是否启用ES搜索引擎",
     *     description="启用后，搜索数据将同步到ES搜索引擎，检索相关的功能将由ES接管",
     *     driver="FormItemSwitch"
     * )
     */
    public int $enable = 0;

    /**
     * @BeConfigItem("搜索数据索引名",
     *     driver="FormItemInput",
     *     ui="return ['form-item' => ['v-show' => 'formData.enable === 1']];"
     * )
     */
    public string $indexSearchItem = 'search.item';

    /**
     * @BeConfigItem("搜索记录索引",
     *     driver="FormItemInput",
     *     ui="return ['form-item' => ['v-show' => 'formData.enable === 1']];"
     * )
     */
    public string $indexSearchHistory = 'search.search_history';

}
