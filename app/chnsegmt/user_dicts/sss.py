#encoding=utf-8

import sys, os, codecs
def w(f, content):
    f.write(content+'\n')

f = open('sentencesegmtdict.txt', 'w')
w(f, '!')
w(f, '?')
w(f, '.')
w(f, '。')
w(f,'！')
w(f,'？')
f.close()
