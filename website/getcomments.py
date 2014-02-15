# getcomments.py
import httplib
import json
import re
import sys
sys.path.append('../chnsegmt')

from basicfuncs import GetTimestamp

def GetComments(para_dict):
    source = para_dict.get('source')
    if source == 'tencent':
        return GetCommentsFromTencent(para_dict)
    elif source == 'netease':
        return GetCommentsFromNetease(para_dict)
    elif source == 'sina':
        return GetCommentsFromSina(para_dict)
    else:
        return None

def GetCommentsFromTencent(para_dict): # currently hot comments
    domain = 'coral.qq.com'
    req = '/article/%s/hotcomment?reqnum=%s&callback=myHotcommentList&_=%s%d' % (para_dict.get('cmtId'), para_dict.get('reqNum', '10'), GetTimestamp(10), 444)
    data = GetDataFromHttp(domain, req)
    return data

def GetCommentsFromNetease(para_dict): # currently hot comments
    domain = 'comment.news.163.com'
    req = '/data/%s/df/%s_1.html' % (para_dict.get('boardId'), para_dict.get('cmtId'))
    data = unicode(GetDataFromHttp(domain, req), 'utf-8').encode('gbk')
    data = re.match(r'^var \w+=({.*});$', data).group(1)
    return data

def GetCommentsFromSina(para_dict):
    domain = 'comment5.news.sina.com.cn'
    req = '/page/info?format=%s&channel=%s&newsid=%s&group=%s&compress=1&ie=gbk&oe=gbk&page=%s&page_size=%s&jsvar=requestId_%s' % ('json', para_dict.get('channelId'), para_dict.get('cmtId'), '0', '1', '100', '444')
    data = GetDataFromHttp(domain, req)
    return data

def GetDataFromHttp(domain, req):
    conn = httplib.HTTPConnection(domain)
    conn.request('GET', req)
    resp = conn.getresponse()
    data = None
    if not (resp.status == 200 and  (resp.reason == 'OK' or resp.reason == 'ok')):
        print 'Error: Get response from ' + domain + req + ' failed'
    else:
        print 'Got data from ' + domain + req
        data = resp.read()
    conn.close()
    return data

