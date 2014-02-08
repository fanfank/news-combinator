# extracttags.py
import codecs
import jieba
import jieba.analyse
import json
import os
import sys

def ExtractTagsFromContent(content, num_of_tags):
    tags = jieba.analyse.extract_tags(content, topK = num_of_tags)
    return tags

def ExtractTagsFromFile(file_path, num_of_tags):
    print 'Extracting from ' + file_path + '...'
    if not (os.path.exists(file_path) and os.path.isfile(file_path)):
        print "Path not exists or not a file"
        sys.exit(2)
    f = codecs.open(file_path, 'r', 'utf-8')
    js = json.load(f)
    content = js['contents']['passage'] # f.read()
    tags = ExtractTagsFromContent(content, num_of_tags) # jieba.analyse.extract_tags(content, topK = num_of_tags)
    print '\t'.join(tags)
    f_tag = codecs.open(file_path[:-5] + ".tags", 'w', 'utf-8')
    f_tag.write('\t'.join(tags))
    f_tag.close()
    f.close()

def ExtractTagsFromDirectory(dir_path, num_of_tags):
    if (not os.path.exists(dir_path)) or os.path.isfile(dir_path):
        print "Path not exists or not a directory"
        sys.exit(3)
    for parent, dir_names, file_names in os.walk(dir_path):
        for file_name in file_names:
            if file_name[-4:] == 'json':
                ExtractTagsFromFile(parent + '/' + file_name, num_of_tags)

