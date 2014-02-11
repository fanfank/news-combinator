# categorize.py
from basicfuncs import IsDirectory, IsFile, MakeDirectory, CopyFile
from extracttags import ExtractTagsFromFile, ExtractTagsFromDirectory
from findsimilarpassage import FindSimilarPassageFromDirectory
from optparse import OptionParser
from tfidf import GetTermFreqFromFile

import codecs
import json
import os
import sys

def Categorize(example_dir, output_dir, source_dirs, num_of_tags):
    if not IsDirectory(example_dir):
        print "example_dir not exists or not a directory"
        sys.exit(3)
    if not IsDirectory(output_dir):
        print "output_dir not exists or not a directory"
        sys.exit(3)

    for parent, dir_names, file_names in os.walk(example_dir):
        for file_name in file_names:
            if file_name[-4:] == 'json':
                print "----------------------------------------"
                print "Searching for " + file_name + "'s similar passage ..."
                file_path = parent + '/' + file_name
                tags = ExtractTagsFromFile(file_path, num_of_tags)
                example_tf = GetTermFreqFromFile(tags, file_path) # a dict
                if example_tf == None:
                    continue
                for source_dir in source_dirs:
                    if source_dir == example_dir:
                        print "source_dir can't be the same as example_dir"
                        continue
                    resfile = FindSimilarPassageFromDirectory(source_dir, example_tf)
                    if resfile == None:
                        print "No similar passage to " + file_name + " in " + source_dir
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


USAGE = "usage: python categorize.py -k number_of_tags -e example_directory -o output_directory source_directories ..."

parser = OptionParser(USAGE)
parser.add_option("-k", dest="number_of_tags", help="the number of tags to be extracted from the passage")
parser.add_option("-e", dest="example_dir", help="the directory in which json files are used as basis")
parser.add_option("-o", dest="output_dir", help="the directory in which the results are stored")

opt, args = parser.parse_args()
num_of_tags = 10
example_dir = ""
output_dir = ""
source_dirs = []

if opt.example_dir is None or opt.output_dir is None or len(args) < 1:
    print USAGE
    sys.exit(1)

if not (opt.number_of_tags is None and opt.number_of_tags >= 1):
    num_of_tags = opt.number_of_tags

example_dir = opt.example_dir
output_dir = opt.output_dir
source_dirs = args

Categorize(example_dir, output_dir, source_dirs, num_of_tags)
