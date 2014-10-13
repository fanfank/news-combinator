USE `reetsee_news`;
CREATE TABLE `news_category` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '自增主键',
    `title` varchar(128) NOT NULL DEFAULT '' COMMENT '新闻标题',
    `source_news_ids` varchar(1024) NOT NULL COMMENT '新闻来源的id，逗号分割',
    `day_time` int(11) UNSIGNED NOT NULL DEFAULT 19700101 COMMENT '日期格式为：YYYYmmdd',
    `preview_pic` varchar(1024) NOT NULL DEFAULT '' COMMENT '新闻网站图标',
    `abstract_ids` varchar(1024) NOT NULL DEFAULT '' COMMENT '关联的news_abstract表中的id',
    INDEX `dt` (`day_time`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='记录已分类的新闻';
