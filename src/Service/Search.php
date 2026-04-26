<?php

namespace Be\App\Search\Service;

use Be\App\ServiceException;
use Be\Be;

class Search
{

    /**
     * 插入或更新搜索数据
     * 供其它应用调用的公共方法
     *
     * @param array $data 搜索数据
     * @return object
     * @throws ServiceException
     */
    public function edit(array $data): object
    {
        $db = Be::getDb();

        $isNew = true;
        $itemId = null;
        if (isset($data['id']) && $data['id'] !== '') {
            $isNew = false;
            $itemId = $data['id'];
        }

        if (!isset($data['app_name']) || !is_string($data['app_name']) || $data['app_name'] === '') {
            throw new ServiceException('应用标识未填写！');
        }

        $appName = $data['app_name'];

        // 查找或自动创建 search_app 记录
        $appLabel = $appName;
        if (isset($data['app_label']) && is_string($data['app_label']) && $data['app_label'] !== '') {
            $appLabel = $data['app_label'];
        }

        $sql = 'SELECT id FROM search_app WHERE name=? AND is_delete=0';
        $appId = $db->getValue($sql, [$appName]);
        if (!$appId) {
            $tupleApp = Be::getTuple('search_app');
            $tupleApp->name = $appName;
            $tupleApp->label = $appLabel;
            $tupleApp->icon = $data['app_icon'] ?? '';
            $tupleApp->is_enable = 1;
            $tupleApp->is_delete = 0;
            $tupleApp->insert();
        } else {
            // 更新 app_label
            if (isset($data['app_label'])) {
                $tupleApp = Be::getTuple('search_app');
                $tupleApp->load($appId);
                $tupleApp->label = $appLabel;
                if (isset($data['app_icon'])) {
                    $tupleApp->icon = $data['app_icon'];
                }
                $tupleApp->update();
            }
        }

        $tupleItem = Be::getTuple('search_item');
        if (!$isNew) {
            try {
                $tupleItem->load($itemId);
            } catch (\Throwable $t) {
                throw new ServiceException('搜索数据（# ' . $itemId . '）不存在！');
            }
        }

        if (!isset($data['title']) || !is_string($data['title']) || $data['title'] === '') {
            throw new ServiceException('标题未填写！');
        }

        $now = date('Y-m-d H:i:s');

        $tupleItem->app_name = $appName;
        $tupleItem->app_label = $appLabel;
        $tupleItem->image = $data['image'] ?? '';
        $tupleItem->title = $data['title'];
        $tupleItem->summary = $data['summary'] ?? '';
        $tupleItem->description = $data['description'] ?? '';
        $tupleItem->url = $data['url'] ?? '';
        $tupleItem->author = $data['author'] ?? '';
        $tupleItem->publish_time = $data['publish_time'] ?? null;
        $tupleItem->ordering = $data['ordering'] ?? 0;
        $tupleItem->hits = $data['hits'] ?? 0;
        $tupleItem->is_enable = $data['is_enable'] ?? 1;
        $tupleItem->is_delete = 0;
        $tupleItem->update_time = $now;

        if ($isNew) {
            $tupleItem->create_time = $now;
            $tupleItem->insert();
        } else {
            $tupleItem->update();
        }

        // 触发同步任务
        Be::getService('App.System.Task')->trigger('Search.SearchItemSyncEs');

        return $tupleItem->toObject();
    }

    /**
     * 删除搜索数据
     * 供其它应用调用的公共方法
     *
     * @param string $itemId 数据ID
     * @return void
     * @throws ServiceException
     */
    public function delete(string $itemId)
    {
        $tupleItem = Be::getTuple('search_item');
        try {
            $tupleItem->load($itemId);
        } catch (\Throwable $t) {
            throw new ServiceException('搜索数据（# ' . $itemId . '）不存在！');
        }

        $tupleItem->is_delete = 1;
        $tupleItem->update_time = date('Y-m-d H:i:s');
        $tupleItem->update();

        // 触发同步任务
        Be::getService('App.System.Task')->trigger('Search.SearchItemSyncEs');
    }

    /**
     * 按应用和原始ID删除搜索数据
     * 供其它应用调用的公共方法
     *
     * @param string $appName 应用标识
     * @param string $id 数据ID
     * @return void
     */
    public function deleteByAppAndId(string $appName, string $id)
    {
        $db = Be::getDb();
        $sql = 'UPDATE search_item SET is_delete=1, update_time=? WHERE app_name=? AND id=? AND is_delete=0';
        $db->query($sql, [date('Y-m-d H:i:s'), $appName, $id]);

        // 触发同步任务
        Be::getService('App.System.Task')->trigger('Search.SearchItemSyncEs');
    }

    /**
     * 同步数据到ES（全量）
     * 供其它应用在计划任务中调用
     *
     * @param string $appName 应用标识，为空时同步所有应用
     * @return void
     */
    public function syncEs(string $appName = '')
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Search.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return;
        }

        $service = Be::getService('App.Search.Admin.TaskSearchItem');
        $db = Be::getDb();

        $sql = 'SELECT * FROM search_item WHERE is_enable=1 AND is_delete=0';
        $bindValues = [];
        if ($appName !== '') {
            $sql .= ' AND app_name=?';
            $bindValues[] = $appName;
        }

        $objs = $db->getYieldObjects($sql, $bindValues);

        $batch = [];
        $i = 0;
        foreach ($objs as $obj) {
            $batch[] = $obj;

            $i++;
            if ($i >= 100) {
                $service->syncEs($batch);
                $service->syncCache($batch);

                $batch = [];
                $i = 0;
            }
        }

        if ($i > 0) {
            $service->syncEs($batch);
            $service->syncCache($batch);
        }
    }

    /**
     * 前台搜索
     *
     * @param string $keywords 关键词
     * @param array $params 查询参数
     * @return array
     */
    public function search(string $keywords, array $params = []): array
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Search.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return $this->searchFromDb($keywords, $params);
        }

        $configSearch = Be::getConfig('App.Search.Search');

        $cache = Be::getCache();
        $es = Be::getEs();

        $keywords = trim($keywords);
        if ($keywords !== '') {
            // 将搜索关键词写入ES search_history
            $counterKey = 'App:Search:SearchHistory';
            $counter = (int)$cache->get($counterKey);
            $query = [
                'index' => $configEs->indexSearchHistory,
                'id' => $counter,
                'body' => [
                    'keyword' => $keywords,
                ]
            ];
            $es->index($query);

            $counter++;
            if ($counter >= $configSearch->searchHistory) {
                $counter = 0;
            }

            $cache->set($counterKey, $counter);
        }

        $cacheKey = 'App:Search:Search';
        if ($keywords !== '') {
            $cacheKey .= ':' . $keywords;
        }
        $cacheKey .= ':' . md5(serialize($params));

        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $query = [
            'index' => $configEs->indexSearchItem,
            'body' => []
        ];

        if ($keywords === '') {
            $query['body']['min_score'] = 0;
        } else {
            $query['body']['min_score'] = 0.01;

            $query['body']['query']['bool']['should'] = [
                [
                    'match' => [
                        'title' => [
                            'query' => $keywords,
                            'boost' => 2,
                        ]
                    ],
                ],
                [
                    'match' => [
                        'summary' => [
                            'query' => $keywords,
                            'boost' => 1,
                        ]
                    ],
                ],
                [
                    'match' => [
                        'description' => [
                            'query' => $keywords,
                            'boost' => 1,
                        ]
                    ],
                ],
            ];
        }

        // 按应用筛选
        if (isset($params['appName']) && $params['appName'] !== '') {
            if (!isset($query['body']['query'])) {
                $query['body']['query'] = [];
            }
            if (!isset($query['body']['query']['bool'])) {
                $query['body']['query']['bool'] = [];
            }
            if (!isset($query['body']['query']['bool']['filter'])) {
                $query['body']['query']['bool']['filter'] = [];
            }

            $query['body']['query']['bool']['filter'][] = [
                'term' => [
                    'app_name' => $params['appName'],
                ]
            ];
        }

        // 排序
        if (isset($params['orderBy'])) {
            $orderByDir = 'desc';
            if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                $orderByDir = $params['orderByDir'];
            }

            $query['body']['sort'] = [
                [
                    $params['orderBy'] => [
                        'order' => $orderByDir
                    ]
                ],
            ];
        }

        // 分页
        $pageSize = null;
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = $configSearch->pageSize;
        }
        if ($pageSize > 200) {
            $pageSize = 200;
        }

        $page = null;
        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $query['body']['size'] = $pageSize;
        $query['body']['from'] = ($page - 1) * $pageSize;

        $results = $es->search($query);

        $total = 0;
        if (isset($results['hits']['total']['value'])) {
            $total = $results['hits']['total']['value'];
        }

        $rows = [];
        foreach ($results['hits']['hits'] as $x) {
            $item = (object)$x['_source'];
            $rows[] = $item;
        }

        $result = [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];

        $configCache = Be::getConfig('App.Search.Cache');
        $cache->set($cacheKey, $result, $configCache->searchItems);

        return $result;
    }

    /**
     * 从数据库搜索
     *
     * @param string $keywords 关键词
     * @param array $params
     * @return array
     */
    public function searchFromDb(string $keywords, array $params = []): array
    {
        $cache = Be::getCache();
        $cacheKey = 'App:Search:SearchFromDb';
        if ($keywords !== '') {
            $cacheKey .= ':' . $keywords;
        }
        $cacheKey .= ':' . md5(serialize($params));

        $results = $cache->get($cacheKey);
        if ($results !== false) {
            return $results;
        }

        $configSearch = Be::getConfig('App.Search.Search');
        $tableItem = Be::getTable('search_item');

        $tableItem->where('is_enable', 1);
        $tableItem->where('is_delete', 0);

        if ($keywords !== '') {
            $tableItem->where('title', 'like', '%' . $keywords . '%');
        }

        if (isset($params['appName']) && $params['appName'] !== '') {
            $tableItem->where('app_name', $params['appName']);
        }

        $total = $tableItem->count();

        if (isset($params['orderBy']) && is_string($params['orderBy'])) {
            $orderByDir = 'DESC';
            if (isset($params['orderByDir']) && in_array($params['orderByDir'], ['asc', 'desc'])) {
                $orderByDir = strtoupper($params['orderByDir']);
            }
            $tableItem->orderBy($params['orderBy'], $orderByDir);
        } else {
            $tableItem->orderBy('update_time', 'DESC');
        }

        // 分页
        $pageSize = null;
        if (isset($params['pageSize']) && is_numeric($params['pageSize']) && $params['pageSize'] > 0) {
            $pageSize = $params['pageSize'];
        } else {
            $pageSize = $configSearch->pageSize;
        }
        if ($pageSize > 200) {
            $pageSize = 200;
        }
        $tableItem->limit($pageSize);

        $page = null;
        if (isset($params['page']) && is_numeric($params['page']) && $params['page'] > 0) {
            $page = $params['page'];
        } else {
            $page = 1;
        }

        $tableItem->offset(($page - 1) * $pageSize);

        $rows = $tableItem->getObjects();

        $result = [
            'total' => $total,
            'pageSize' => $pageSize,
            'page' => $page,
            'rows' => $rows,
        ];

        $configCache = Be::getConfig('App.Search.Cache');
        $cache->set($cacheKey, $result, $configCache->searchItems);

        return $result;
    }

    /**
     * 从搜索历史中提取热门搜索词
     *
     * @param int $n
     * @return array
     */
    public function getHotSearchKeywords(int $n = 6): array
    {
        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Search.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return [];
        }

        $cache = Be::getCache();
        $cacheKey = 'App:Search:HotSearchKeywords';
        $topSearchKeywords = $cache->get($cacheKey);
        if ($topSearchKeywords) {
            return $topSearchKeywords;
        }

        $es = Be::getEs();
        $query = [
            'index' => $configEs->indexSearchHistory,
            'body' => [
                'size' => 0,
                'aggs' => [
                    'topN' => [
                        'terms' => [
                            'field' => 'keyword',
                            'size' => $n
                        ]
                    ]
                ]
            ]
        ];

        $result = $es->search($query);

        $hotKeywords = [];
        if (isset($result['aggregations']['topN']['buckets']) &&
            is_array($result['aggregations']['topN']['buckets']) &&
            count($result['aggregations']['topN']['buckets']) > 0
        ) {
            foreach ($result['aggregations']['topN']['buckets'] as $v) {
                $hotKeywords[] = $v['key'];
            }
        }

        $configCache = Be::getConfig('App.Search.Cache');
        $cache->set($cacheKey, $hotKeywords, $configCache->hotKeywords);

        return $hotKeywords;
    }

}
