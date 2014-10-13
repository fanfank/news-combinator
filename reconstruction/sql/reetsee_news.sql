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

USE `reetsee_news`;
CREATE TABLE `news_content` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '自增主键',
    `title` varchar(128) NOT NULL DEFAULT '' COMMENT '新闻标题',
    `source_name` varchar(32) NOT NULL DEFAULT '' COMMENT '新闻来源',
    `content` text NOT NULL COMMENT '新闻详细内容',
    `source_news_link` varchar(1024) NOT NULL DEFAULT 'http://blog.reetsee.com/404' COMMENT '文章链接',
    `source_comment_link` varchar(1024) NOT NULL DEFAULT 'http://blog.reetsee.com/404' COMMENT '原文评论页面链接',
    `source_news_id` varchar(64) NOT NULL COMMENT '文章在来源处的id',
    `source_comment_id` varchar(64) NOT NULL DEFAULT '' COMMENT '文章在来源处的评论id',
    `abstract_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新闻摘要内容对应的id值',
    `timestamp` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间戳',
    `ext` varchar(2048) DEFAULT '' COMMENT '扩展字段',
    UNIQUE KEY `aid` (`abstract_id`),
    UNIQUE KEY `snid` (`source_news_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用于保存新闻相关详细信息';

USE `reetsee_news`;
CREATE TABLE `news_category` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '自增主键',
    `title` varchar(128) NOT NULL DEFAULT '' COMMENT '新闻标题',
    `source_names` varchar(1024) NOT NULL COMMENT '新闻来源的名字，逗号分割',
    `day_time` int(11) UNSIGNED NOT NULL DEFAULT 19700101 COMMENT '日期格式为：YYYYmmdd',
    `preview_pic` varchar(1024) NOT NULL DEFAULT '' COMMENT '新闻网站图标',
    `abstract_ids` varchar(1024) NOT NULL DEFAULT '' COMMENT '关联的news_abstract表中的id',
    INDEX `dt` (`day_time`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='记录已分类的新闻';
