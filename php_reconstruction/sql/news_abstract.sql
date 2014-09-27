USE `reetsee_news`;
CREATE TABLE `news_abstract` (
    `id` int(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '自增主键',
    `title` varchar(128) NOT NULL DEFAULT '' COMMENT '新闻标题',
    `icon_pic` varchar(1024) NOT NULL DEFAULT '' COMMENT '新闻网站图标',
    `rate_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '一共打的分数',
    `rate_counts` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '一共打分的人数',
    `quality` int(10) NOT NULL DEFAULT 0 COMMENT '后续引入排序策略的时候需要考虑的质量度',
    `content_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新闻详细内容ID',
    `source_news_id` varchar(64) NOT NULL COMMENT '文章在来源处的id',
    `day_time` int(11) UNSIGNED NOT NULL DEFAULT 19700101 COMMENT '日期格式为：YYYYmmdd',
    `timestamp` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间戳',
    INDEX  `dt` (`day_time`),
    UNIQUE KEY `snid` (`source_news_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用于保存新闻摘要信息';
