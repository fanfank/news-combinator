import re

from scrapy.contrib.spiders import CrawlSpider, Rule
from scrapy.contrib.linkextractors.sgml import SgmlLinkExtractor
from scrapy.selector import Selector

from netease.items import NeteaseItem
from netease.news_pack.news_func import ListCombiner

class NeteaseNewsSpider(CrawlSpider):
    name = 'netease_news_spider'
    allowed_domains = ['news.163.com']
    start_urls = ['http://news.163.com']
    url_pattern = r'(http://news\.163\.com)/(\d{2})/(\d{4})/\d+/(\w+)\.html'
    rules = [Rule(SgmlLinkExtractor(allow=[url_pattern]), 'parse_news')]

    def parse_news(self, response):
        sel = Selector(response)
        pattern = re.match(self.url_pattern, str(response.url))

        item = NeteaseItem()
        item['source'] = pattern.group(1)
        item['date'] = '20' + pattern.group(2) + pattern.group(3)
        item['newsId'] = pattern.group(4)
        item['cmtId'] = item['newsId']
        item['boardId'] = sel.re(r"boardId = \"(.*)\"")[0]
        item['comments'] = {'link':str('http://comment.news.163.com/'+item['boardId']+'/'+item['cmtId']+'.html')}
        item['contents'] = {'link':str(response.url), 'title':u'', 'passage':u''}
        item['contents']['title'] = sel.xpath("//h1[@id='h1title']/text()").extract()[0]
        item['contents']['passage'] = ListCombiner(sel.xpath('//p/text()').extract())
        return item
