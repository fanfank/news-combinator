# tfidf.py
# encoding = utf-8
from basicfuncs import IsFile

import codecs
import jieba
import json

def GetTermFreqFromContent(tags, content):
    tfdict = {}
    for tag in tags:
        tfdict[tag] = 0

    seg_list = jieba.cut(content)
    has_words = False
    for word in seg_list:
        if tfdict.has_key(word):
            tfdict[word] = tfdict[word] + 1
            has_words = True

    if has_words:
        return tfdict
    else:
        return None

def GetTermFreqFromFile(tags, file_path):
    if not IsFile(file_path):
        print file_path + " not exists or not a file, can't get TF"
        return None
    f = codecs.open(file_path, 'r', 'utf-8')
    js = json.load(f)
    passage = js['contents']['passage']
    f.close()
    return GetTermFreqFromContent(tags, passage)

