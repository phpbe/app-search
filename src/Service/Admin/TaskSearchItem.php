<?php

namespace Be\App\Search\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class TaskSearchItem
{

    /**
     * 同步到 ES
     *
     * @param array $items
     */
    public function syncEs(array $items)
    {
        if (count($items) === 0) return;

        $configSystemEs = Be::getConfig('App.System.Es');
        $configEs = Be::getConfig('App.Search.Es');
        if ($configSystemEs->enable === 0 || $configEs->enable === 0) {
            return;
        }

        $es = Be::getEs();

        $batch = [];
        foreach ($items as $item) {

            $item->is_enable = (int)$item->is_enable;
            $item->is_delete = (int)$item->is_delete;

            if ($item->is_delete !== 0 || $item->is_enable !== 1) {
                $params = [
                    'body' => [
                        'index' => $configEs->indexSearchItem,
                        'id' => $item->id,
                    ]
                ];

                $es->delete($params);

            } else {

                $batch[] = [
                    'index' => [
                        '_index' => $configEs->indexSearchItem,
                        '_id' => $item->id,
                    ]
                ];

                $batch[] = [
                    'id' => $item->id,
                    'app_name' => $item->app_name,
                    'app_label' => $item->app_label,
                    'image' => $item->image,
                    'title' => $item->title,
                    'summary' => $item->summary,
                    'description' => $item->description,
                    'url' => $item->url,
                    'author' => $item->author,
                    'hits' => (int)$item->hits,
                    'publish_time' => $item->publish_time,
                    'is_enable' => $item->is_enable === 1,
                ];
            }
        }

        if (count($batch) > 0) {
            $response = $es->bulk(['body' => $batch]);
            if ($response['errors'] > 0) {
                $reason = '';
                if (isset($response['items']) && count($response['items']) > 0) {
                    foreach ($response['items'] as $item) {
                        if (isset($item['index']['error']['reason'])) {
                            $reason = $item['index']['error']['reason'];
                            break;
                        }
                    }
                }
                throw new ServiceException('搜索数据同步到ES出错：' . $reason);
            }
        }
    }

    /**
     * 同步到缓存
     *
     * @param array $items
     */
    public function syncCache(array $items)
    {
        if (count($items) === 0) return;

        $cache = Be::getCache();
        $keyValues = [];
        foreach ($items as $item) {

            $item->is_enable = (int)$item->is_enable;
            $item->is_delete = (int)$item->is_delete;

            $key = 'App:Search:SearchItem:' . $item->id;

            if ($item->is_delete !== 0 || $item->is_enable !== 1) {
                $cache->delete($key);
            } else {
                $newItem = new \stdClass();
                $newItem->id = $item->id;
                $newItem->app_name = $item->app_name;
                $newItem->app_label = $item->app_label;
                $newItem->image = $item->image;
                $newItem->title = $item->title;
                $newItem->summary = $item->summary;
                $newItem->description = $item->description;
                $newItem->url = $item->url;
                $newItem->author = $item->author;
                $newItem->publish_time = $item->publish_time;
                $newItem->hits = $item->hits;

                $keyValues[$key] = $newItem;
            }
        }

        if (count($keyValues) > 0) {
            $cache->setMany($keyValues);
        }
    }

}
