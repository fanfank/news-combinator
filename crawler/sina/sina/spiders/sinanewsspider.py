import re

from scrapy.contrib.spiders import CrawlSpider, Rule
from scrapy.contrib.linkextractors.sgml import SgmlLinkExtractor
from scrapy.selector import Selector

from sina.items import SinaItem
from sina.news_pack.news_func import ListCombiner

class SinaNewsSpider(CrawlSpider):
    name = 'sina_news_spider'
    allowed_domains = ['news.sina.com.cn']
    start_urls = ['http://news.sina.com.cn']
    url_pattern = r'(http://(?:\w+\.)*news\.sina\.com\.cn)/.*/(\d{4}-\d{2}-\d{2})/\d{4}(\d{8})\.(?:s)html'
    rules = [Rule(SgmlLinkExtractor(allow=[url_pattern]), 'parse_news')]

    def parse_news(self, response):
        sel = Selector(response)
        pattern = re.match(self.url_pattern, str(response.url))
        
        item = SinaItem()
        item['source'] = pattern.group(1)
        item['date'] = ListCombiner(str(pattern.group(2)).split('-'))
        item['newsId'] = sel.re(r'comment_id:(\d-\d-\d+)')[0]
        item['cmtId'] = item['newsId']
        item['channelId'] = sel.re(r'comment_channel:(\w+);')[0]
        item['comments'] = {'link':str('http://comment5.news.sina.com.cn/comment/skin/default.html?channel='+item['channelId']+'&newsid='+item['cmtId'])}
        item['contents'] = {'link':str(response.url), 'title':u'', 'passage':u''}
        item['contents']['title'] = sel.xpath("//h1[@id='artibodyTitle']/text()").extract()[0]
        item['contents']['passage'] = ListCombiner(sel.xpath('//p/text()').extract())
        return item






