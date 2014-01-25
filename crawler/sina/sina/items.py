# Define here the models for your scraped items
#
# See documentation in:
# http://doc.scrapy.org/en/latest/topics/items.html

from scrapy.item import Item, Field
from sina.news_pack import NewsItem

class SinaItem(NewsItem):
    # define the fields for your item here like:
    # name = Field()
    channelId = Field()
