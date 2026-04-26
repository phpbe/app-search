<?php

namespace Be\App\Search\Service\Admin;

use Be\App\ServiceException;
use Be\Be;

class Es
{

    public function getIndexes()
    {
        $configEs = Be::getConfig('App.Search.Es');
        if (!$configEs->enable) {
            return false;
        }

        $indexes = [];

        $es = Be::getEs();
        foreach ([
                     [
                         'name' => 'searchItem',
                         'label' => '搜索数据索引',
                         'value' => $configEs->indexSearchItem,
                     ],
                     [
                         'name' => 'searchHistory',
                         'label' => '搜索记录索引',
                         'value' => $configEs->indexSearchHistory,
                     ],
                 ] as $index) {
            $params = [
                'index' => $index['value'],
            ];
            if ($es->indices()->exists($params)) {
                $index['exists'] = true;

                $mapping = $es->indices()->getMapping($params);
                $index['mapping'] = $mapping[$index['value']]['mappings'] ?? [];

                $settings = $es->indices()->getSettings($params);
                $index['settings'] = $settings[$index['value']]['settings'] ?? [];

                $count = $es->count($params);
                $index['count'] = $count['count'] ?? 0;
            } else {
                $index['exists'] = false;
            }
            $indexes[] = $index;
        }

        return $indexes;
    }

    /**
     * 创建索引
     *
     * @param string $indexName 索引名
     * @param array $options 参数
     * @return void
     */
    public function createIndex(string $indexName, array $options = [])
    {
        $number_of_shards = $options['number_of_shards'] ?? 2;
        $number_of_replicas = $options['number_of_replicas'] ?? 1;

        $configEs = Be::getConfig('App.Search.Es');
        if ($configEs->enable) {
            $es = Be::getEs();

            $configField = 'index' . ucfirst($indexName);

            $params = [
                'index' => $configEs->$configField,
            ];

            if ($es->indices()->exists($params)) {
                throw new ServiceException('索引（' . $configEs->$configField . '）已存在');
            }

            switch ($indexName) {
                case 'searchItem':
                    $mapping = [
                        'properties' => [
                            'id' => [
                                'type' => 'keyword',
                            ],
                            'app_name' => [
                                'type' => 'keyword',
                            ],
                            'app_label' => [
                                'type' => 'keyword',
                            ],
                            'image' => [
                                'type' => 'keyword',
                            ],
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word',
                            ],
                            'summary' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word',
                            ],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word',
                            ],
                            'url' => [
                                'type' => 'keyword',
                            ],
                            'author' => [
                                'type' => 'keyword',
                            ],
                            'hits' => [
                                'type' => 'integer'
                            ],
                            'publish_time' => [
                                'type' => 'date',
                                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
                            ],
                            'is_enable' => [
                                'type' => 'boolean'
                            ],
                        ]
                    ];
                    break;
                case 'searchHistory':
                    $mapping = [
                        'properties' => [
                            'keyword' => [
                                'type' => 'keyword',
                            ],
                        ]
                    ];
                    break;
            }

            $params = [
                'index' => $configEs->$configField,
                'body' => [
                    'settings' => [
                        'number_of_shards' => $number_of_shards,
                        'number_of_replicas' => $number_of_replicas
                    ],
                    'mappings' => $mapping,
                ]
            ];

            $es->indices()->create($params);
        }
    }

    /**
     * 删除索引
     *
     * @param string $indexName 索引名
     * @return void
     */
    public function deleteIndex(string $indexName)
    {
        $configEs = Be::getConfig('App.Search.Es');
        if ($configEs->enable) {
            $es = Be::getEs();

            $configField = 'index' . ucfirst($indexName);

            $params = [
                'index' => $configEs->$configField,
            ];

            if ($es->indices()->exists($params)) {
                $es->indices()->delete($params);
            }
        }
    }

}
