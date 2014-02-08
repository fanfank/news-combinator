import jieba
import sys

from optparse import OptionParser

USAGE = "usage: python jb_f6_tokenize.py [file name]"

parser = OptionParser(USAGE)
opt, args = parser.parse_args()

if len(args) < 1:
    print USAGE
    sys_exit(1)

file_name = args[0]
fl = open(file_name, 'r')
content = fl.read().decode('utf8')
result = jieba.tokenize(content)

print '--------In Default Mode--------'
for tk in result:
    print "word %s\t\t start: %d \t\t end:%d" % (tk[0], tk[1], tk[2])

print '--------In Search Mode--------'
result = jieba.tokenize(content, mode = 'search')
for tk in result:
    print "word %s\t\t start: %d \t\t end:%d" % (tk[0], tk[1], tk[2])

fl.close()
