# categorize.py
from basicfuncs import IsDirectory, IsFile, MakeDirectory, CopyFile
from extracttags import ExtractTagsFromFile
from findsimilarpassage import FindSimilarPassageFromSet
from optparse import OptionParser
from sets import Set
from tfidf import GetTermFreqFromFile

import codecs
import json
import os
import re
import sys
import time

def Categorize(source_dirs, output_dir, num_of_tags):
    if not IsDirectory(output_dir):
        print "output_dir not exists or not a directory"
        sys.exit(3)

    source_sets = source_dirs # Set(source_dirs) # will disrupt the original order

    news_sets = [] # a list of sets
    for source_dir in source_sets:
        if not IsDirectory(source_dir):
            print "Error. source_dir: " + source_dir + " not exists or not a directory"
            continue
        else:
            tmp_set = Set([])
            for parent, dir_names, file_names in os.walk(source_dir):
                pattern = re.match(r'.*/([0-9]{8})[/]{,1}$', parent)
                if pattern != None and os.stat(parent).st_mtime < last_mtime: #pattern.group(1) < OldestDir:
                    print 'Directory: ' + parent + ' is too old, skipped'
                    continue
                for file_name in file_names:
                    if file_name[-4:] == 'json' and os.stat(parent + '/' + file_name).st_mtime >= last_mtime:
                        tmp_set.add(parent + '/' + file_name)
            news_sets.append(tmp_set)

    for i in range(0, len(news_sets) - 1):
        for file_path in news_sets[i]:
            file_name = re.match(r".*/([-\w]+\.json)", file_path).group(1)
            print "---------------------------------"
            print "Searching for " + file_name + "'s similar passages ..."

            tags = ExtractTagsFromFile(file_path, num_of_tags)
            example_tf = GetTermFreqFromFile(tags, file_path) # a dict
            if example_tf == None:
                continue
            for j in range(i + 1, len(news_sets)):
                resfile = FindSimilarPassageFromSet(news_sets[j], example_tf)
                if resfile == None:
                    #print "No similar passage to " + file_name + " in "
                    continue
                else:
                    f = codecs.open(file_path, 'r', 'utf-8')
                    js = json.load(f)
                    date = js['date']
                    newsId = js['newsId']
                    f.close()

                    result_path = output_dir + '/' + date + '/' + newsId + '/'
                    MakeDirectory(result_path)

                    if not os.path.exists(result_path + '/' + file_name):
                        CopyFile(file_path, result_path)
                    CopyFile(resfile, result_path)
                    print "found similar passage to " + file_name + ": " + resfile


USAGE = "usage: python categorize.py -k number_of_tags -o output_directory source_directories ..."

parser = OptionParser(USAGE)
parser.add_option("-k", dest="number_of_tags", help="the number of tags to be extracted from the passage")
parser.add_option("-o", dest="output_dir", help="the directory in which the results are stored")

opt, args = parser.parse_args()
num_of_tags = 10
output_dir = ""
source_dirs = []

if opt.output_dir is None or len(args) < 2:
    print USAGE
    sys.exit(1)

if not (opt.number_of_tags is None and opt.number_of_tags >= 1):
    num_of_tags = opt.number_of_tags

output_dir = opt.output_dir
source_dirs = args

f = codecs.open(os.path.join(output_dir, 'magicnumber'), 'r', 'utf-8')
timestamp = f.read()
last_mtime = int(re.match('([0-9]{10}).*', timestamp).group(1))
f.close()
OldestDir = time.strftime('%Y%m%d', time.localtime(float(timestamp)))

Categorize(source_dirs, output_dir, num_of_tags)

timestamp = str(time.time())
f = codecs.open(os.path.join(output_dir, 'magicnumber'), 'w', 'utf-8')
f.write(timestamp)
f.close()
