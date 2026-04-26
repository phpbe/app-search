<?php

namespace Be\App\Search\Controller;

use Be\Be;

/**
 * 前台搜索控制器
 */
class Search
{

    /**
     * 搜索页
     */
    public function index()
    {
        $request = Be::getRequest();
        $response = Be::getResponse();

        $keywords = $request->get('keywords', '');
        $appName = $request->get('app_name', '');
        $page = $request->get('page', 1, 'int');
        $pageSize = $request->get('page_size', 0, 'int');

        $params = [
            'page' => $page,
        ];

        if ($appName !== '') {
            $params['appName'] = $appName;
        }

        if ($pageSize > 0) {
            $params['pageSize'] = $pageSize;
        }

        $result = Be::getService('App.Search.Search')->search($keywords, $params);

        $response->set('keywords', $keywords);
        $response->set('appName', $appName);
        $response->set('result', $result);

        // 获取应用列表（用于筛选）
        $db = Be::getDb();
        $sql = 'SELECT name, label FROM search_app WHERE is_delete=0 AND is_enable=1 ORDER BY ordering ASC';
        $apps = $db->getObjects($sql);
        $response->set('apps', $apps);

        // 热搜关键词
        $hotKeywords = Be::getService('App.Search.Search')->getHotSearchKeywords(10);
        $response->set('hotKeywords', $hotKeywords);

        $response->set('title', $keywords !== '' ? '搜索：' . $keywords : '全站搜索');
        $response->display();
    }

}
