# main.py
from extracttags import ExtractTagsFromFile, ExtractTagsFromDirectory
from optparse import OptionParser

import sys

USAGE = "usage: python categorize.py [-e example_directory_path | -f file_path] -k number_of_tags"

parser = OptionParser(USAGE)
parser.add_option("-d", dest="dir_path", help="the directory in which json files are analysed, can't be used with -f option")
parser.add_option("-f", dest="file_path", help="the file which to be analysed, can't be used with -d option")
parser.add_option("-k", dest="number_of_tags", help="the number of tags to be extracted from the passage")

opt, args = parser.parse_args()
num_of_tags = 10
file_path = ""
dir_path = ""

if not opt.number_of_tags is None:
    num_of_tags = int(opt.number_of_tags)

if (opt.dir_path is None and opt.file_path is None) or (not opt.dir_path is None and not opt.file_path is None):
    print USAGE
    sys.exit(1)
elif opt.dir_path is None:
    file_path = opt.file_path
    ExtractTagsFromFile(file_path, num_of_tags = num_of_tags)
else:
    dir_path = opt.dir_path
    ExtractTagsFromDirectory(dir_path, num_of_tags = num_of_tags)




