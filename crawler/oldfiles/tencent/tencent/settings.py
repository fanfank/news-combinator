# Scrapy settings for tencent project
#
# For simplicity, this file contains only the most important settings by
# default. All the other settings are documented here:
#
#     http://doc.scrapy.org/en/latest/topics/settings.html
#

BOT_NAME = 'tencent'

SPIDER_MODULES = ['tencent.spiders']
NEWSPIDER_MODULE = 'tencent.spiders'
ITEM_PIPELINES = {
        'tencent.pipelines.TencentPipeline': 1,
        }

# Crawl responsibly by identifying yourself (and your website) on the user-agent
#USER_AGENT = 'tencent (+http://www.yourdomain.com)'
