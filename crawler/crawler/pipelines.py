# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: http://doc.scrapy.org/en/latest/topics/item-pipeline.html
import codecs
import json
import os

class CrawlerPipeline(object):
    def __init__(self):
        self.current_dir = os.getcwd()

    def process_item(self, item, spider):
        dir_path = self.current_dir + '/docs/' + item['source'] + '/' + item['date']
        if not os.path.exists(dir_path):
            os.mkdir(dir_path)

        news_file = codecs.open(dir_path + '/' + item['newsId'] + '.json', 'w', 'utf-8')
        line = json.dumps(dict(item))
        news_file.write(line)
        news_file.close()
        return item
