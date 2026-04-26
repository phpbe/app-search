CREATE TABLE `search_app` (
`id` varchar(36) NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
`name` varchar(60) NOT NULL DEFAULT '' COMMENT '应用标识（如Cms, Doc等）',
`label` varchar(120) NOT NULL DEFAULT '' COMMENT '应用名称',
`icon` varchar(60) NOT NULL DEFAULT '' COMMENT '应用图标',
`ordering` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
`is_enable` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否启用',
`is_delete` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已删除',
`create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci COMMENT='搜索应用';


CREATE TABLE `search_item` (
`id` varchar(36) NOT NULL DEFAULT 'uuid()' COMMENT 'UUID',
`app_name` varchar(60) NOT NULL DEFAULT '' COMMENT '所属应用标识',
`app_label` varchar(120) NOT NULL DEFAULT '' COMMENT '所属应用名称',
`image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图片',
`title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
`summary` varchar(500) NOT NULL DEFAULT '' COMMENT '摘要',
`description` mediumtext NOT NULL COMMENT '描述',
`url` varchar(500) NOT NULL DEFAULT '' COMMENT '网址',
`author` varchar(60) NOT NULL DEFAULT '' COMMENT '作者',
`publish_time` timestamp NULL DEFAULT NULL COMMENT '发布时间',
`ordering` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
`hits` int(11) NOT NULL DEFAULT '0' COMMENT '点击量',
`is_enable` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否启用',
`is_delete` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已删除',
`create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci COMMENT='搜索数据';


ALTER TABLE `search_app`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `search_item`
ADD PRIMARY KEY (`id`),
ADD KEY `app_name` (`app_name`),
ADD KEY `update_time` (`update_time`);
