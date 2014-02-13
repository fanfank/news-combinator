# reetseenews.py
from datastructures import News, DateDir
from flask import Flask, redirect, url_for, g, abort, render_template, flash
import codecs
import os
import sys
sys.path.append('../chnsegmt')

from basicfuncs import IsDirectory

# create the application
app = Flask(__name__)
app.config.from_object(__name__)

# update environment variables
app.config.update(dict(
    DEBUG = True,
    SECRET_KEY = 'news combinator'
    ))

app.config.from_envvar('REETSEE_NEWS_SETTINGS', silent = True)
NEWSDIR = os.path.join(app.root_path, '../result')

def get_magic_number():
    f = codecs.open(os.path.join(NEWSDIR, 'magicnumber'), 'r', 'utf-8')
    magicnumber = f.read()
    f.close()
    return magicnumber

#------test
def print_entries(entries):
    for entry in entries:
        print 'Date: ' + entry.date
        for news in entry.news:
            print '   news: ' + news.title
            if 'tencent' in news.sources:
                print '        tencent',
            if 'netease' in news.sources:
                print 'netease',
            if 'sina' in news.sources:
                print 'sina'
        #    print os.path.join(news.parent_dir, news.dir_name)
#----------


def get_entries(timespan = 5):
    if (not hasattr(g, 'entries')) or g.magicnumber != get_magic_number():
        g.magicnumber = get_magic_number()
        print 'Update time: ' + g.magicnumber
        g.entries = []
        for dir_name in os.listdir(NEWSDIR):
            if IsDirectory(os.path.join(NEWSDIR, dir_name)):
                g.entries.append(DateDir(NEWSDIR, dir_name))
                g.entries[-1].get_news()
        g.entries.sort(reverse = True)
    return g.entries

@app.teardown_appcontext
def tidy(error):
    pass

@app.route('/')
def show_news():
    entries = get_entries(5)
    return render_template('show_entries.html', entries = entries)

if __name__ == '__main__':
    app.run()
