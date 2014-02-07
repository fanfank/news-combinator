# Define here the models for your scraped items
#
# See documentation in:
# http://doc.scrapy.org/en/latest/topics/items.html

from scrapy.item import Item, Field
from news_pack.news_item import NewsItem

#class CrawlerItem(Item):
    # define the fields for your item here like:
    # name = Field()
    #pass

class TencentItem(NewsItem):
    pass

class NeteaseItem(NewsItem):
    boardId = Field()

class SinaItem(NewsItem):
    channelId = Field()
