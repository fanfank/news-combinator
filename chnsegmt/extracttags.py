# extracttags.py
from basicfuncs import IsDirectory, IsFile

import codecs
import jieba
import jieba.analyse
import json
import os
import sys

def WriteTagsToFile(file_path, tags, encoding):
    print '\t'.join(tags)
    f_tag = codecs.open(file_path, 'w', encoding)
    f_tag.write('\t'.join(tags))
    f_tag.close()

def ExtractTagsFromContent(content, num_of_tags):
    tags = jieba.analyse.extract_tags(content, topK = num_of_tags)
    return tags

def ExtractTagsFromFile(file_path, num_of_tags):
#    print 'Extracting from ' + file_path + ' ...'
    if not IsFile(file_path):
        print "Path not exists or not a file"
        sys.exit(2)
    f = codecs.open(file_path, 'r', 'utf-8')
    js = json.load(f)
    content = js['contents']['passage'] # f.read()
    tags = ExtractTagsFromContent(content, num_of_tags) # jieba.analyse.extract_tags(content, topK = num_of_tags)
    f.close()
    return tags

def ExtractTagsFromDirectory(dir_path, num_of_tags):
    if not IsDirectory(dir_path):
        print "Path not exists or not a directory"
        sys.exit(3)
    for parent, dir_names, file_names in os.walk(dir_path):
        for file_name in file_names:
            if file_name[-4:] == 'json':
                file_path = parent + '/' + file_name
                tags = ExtractTagsFromFile(file_path, num_of_tags)
                WriteTagsToFile(file_path[:-5] + '.tags', tags, 'utf-8')
