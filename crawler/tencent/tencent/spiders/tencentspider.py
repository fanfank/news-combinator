import re

from scrapy.contrib.spiders import CrawlSpider, Rule
from scrapy.contrib.linkextractors.sgml import SgmlLinkExtractor
from scrapy.selector import Selector

from tencent.items import TencentItem

def ListCombiner(lst):
    string = ''
    for e in lst:
        string += e
    return string

class TencentNewsSpider(CrawlSpider):
    name = 'tencent_news_spider'
    allowed_domains = ['news.qq.com']
    start_urls = ['http://news.qq.com']
    rules = [Rule(SgmlLinkExtractor(allow=['http://news\.qq\.com/a/\d{8}/\d*\.htm']), 'parse_news')]
    
    def parse_news(self, response):
        sel = Selector(response)
        pattern = re.match(r'(.*)/a/(\d{8})/(\d+)\.htm$', str(response.url))

        item = TencentItem()
        item['source'] = pattern.group(1)
        item['date'] = pattern.group(2)
        item['newsId'] = pattern.group(3)
        item['cmtId'] = sel.re(r"cmt_id = (.*);")[0] # unicode string
        item['contents'] = {'link':str(response.url), 'title':u'', 'passage':u''}
        item['comments'] = {'link':str('http://coral.qq.com/')+item['cmtId']}

        item['contents']['title'] = sel.xpath('//h1/text()').extract()[0]
        item['contents']['passage'] = ListCombiner(sel.xpath('//p/text()').extract())
        return item

