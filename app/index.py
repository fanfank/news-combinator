#-*- coding:utf-8 -*-
from datastructures import News, DateDir
from flask import Flask, request, redirect, url_for, g, abort, render_template, flash
from getcomments import GetComments
import codecs
import json
#import jieba
#jieba.set_dictionary("jieba/dict.txt.big")
#jieba.initialize()

import os
import sys
import logging
logging.basicConfig(filename = '/home/bae/log/reetseenews.log.1', level = logging.WARN, filemode = 'w', format = '%(asctime)s - %(levelname)s: %(message)s')
log_console = logging.StreamHandler(sys.stderr)
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)
logger.addHandler(log_console)

sys.path.append('./chnsegmt')

from basicfuncs import IsDirectory, IsFile
from getabstract import GetPassageAbstract

# create the application
app = Flask(__name__)
app.debug = True
app.config.from_object(__name__)

# update environment variables
app.config.update(dict(
    DEBUG = True,
    SECRET_KEY = 'news combinator'
    ))

app.config.from_envvar('REETSEE_NEWS_SETTINGS', silent = True)
NEWSDIR = os.path.join(app.root_path, './result')

from bae.core.wsgi import WSGIApplication
application = WSGIApplication(app)

def dated_url_for(endpoint, **values):
    if endpoint == 'static':
        filename = values.get('filename', None)
        if filename:
            file_path = os.path.join(app.root_path,
                                     endpoint, filename)
            values['q'] = int(os.stat(file_path).st_mtime)
    return url_for(endpoint, **values)

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

def get_magic_number():
    f = codecs.open(os.path.join(NEWSDIR, 'magicnumber'), 'r', 'utf-8')
    magicnumber = f.read()
    f.close()
    return magicnumber

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

@app.context_processor
def override_url_for():
    return dict(url_for=dated_url_for)

@app.teardown_appcontext
def tidy(error):
    pass

@app.route('/')
def show_news():
    #print >> sys.stderr,'in main page';
    logger.debug('in main page');
    entries = get_entries(5)
    return render_template('show_entries.html', entries = entries)

@app.route('/error')
def error_page():
    error = request.args.get('errcode')
    if not error == None:
        return error
    else:
        return "Something wrong happends"

@app.route('/view/<date>/<dir_name>')
def view_entry(date, dir_name):
    #logging.warning('view_entry');
    logger.debug('view_entry');

    dir_path = os.path.join(NEWSDIR, date, dir_name)
    if not IsDirectory(dir_path):
        return redirect(url_for('error_page', errcode = 'No this directory'))
    # get news contents and news comments
    news = {}
    comments = []
    for file_name in os.listdir(dir_path):
        file_path = os.path.join(dir_path, file_name)
        if IsFile(file_path) and file_name[-4:] == 'json':
            f = codecs.open(file_path, 'r', 'utf-8')
            js = json.load(f)
            f.close()
            news[js['source']] = js
            comments.extend(GetComments(js))

    # sort the comments
    comments.sort(key=lambda x:x['time'])
    comment_abstract = GetPassageAbstract('\n'.join([comment['content'] for comment in comments]), 0.5, 0.1, '|')
    #comment_abstract.encode('utf-8')
    return render_template('view_news.html', news = news, comments = comments, comment_abstract = comment_abstract)

if __name__ == '__main__':
    app.run()
