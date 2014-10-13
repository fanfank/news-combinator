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
