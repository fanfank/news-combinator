# buildvectors.py
from basicfuncs import TrimSpaces, UniqueList
from sets import Set

import jieba
import jieba.posseg as pseg
import sys
sys.path.append('./user_dicts/')

elmn_dict = {}
f = open('user_dicts/elmnattrdict.txt', 'r')
lines = f.readlines()
f.close()
for line in lines:
    item = line.split()
    elmn_dict[item[0]] = int(item[1])
#print elmn_dict

class Sentence():
    f = open('user_dicts/sentencesegmtdict.txt', 'r')
    segmt_list = map(lambda x: unicode(x[:-1], 'utf-8'), f.readlines())
    segmt_list.append(u'\n')
    f.close()
    def __init__(self, content, weight = 0):
        self.content = content
        self.weight = float(weight)

def GetAbstract(sentences, tags, sentences_factor):
    tags_set = Set(tags)
    for sentence in sentences:
        words = list(jieba.cut(sentence.content))
        for word in words:
            if word in tags_set:
                sentence.weight += 1.0/len(words)
    PRINT(sentences)
    result = sorted(sentences, key=lambda s:s.weight, reverse=True)
    print '__________________________'
    PRINT(result)
    print '__________________________'

    thresh_index = int(min((round(len(sentences)*sentences_factor), len(sentences) - 1)))
    thresh = result[thresh_index].weight
    print thresh_index, len(sentences), thresh
    ans = ['']
    for sentence in sentences:
        if sentence.weight > 0 and sentence.weight > thresh:
            ans.append(sentence.content)
    return ''.join(ans)

def GetPassageAbstract(passage, keys_factor = 0.5, sentences_factor = 0.8):
    if keys_factor <= 0 or keys_factor > 1:
        print "Error: keys_factor: " + keys_factor + " illegal, corrected to 0.5"
        keys_factor = 0.5
    if sentences_factor <= 0 or sentences_factor > 1:
        print 'Error: sentences_factor: ' + sentences_factor + ' illegal, corrected to 0.8'
        sentences_factor = 0.8
    passage = TrimSpaces(passage)    
    sentences = GetPassageSentences(passage)
    tags = GetPassageTags(passage, keys_factor)
    return GetAbstract(sentences, tags, sentences_factor)

def GetPassageSentences(passage):
    text = passage #unicode(passage, 'utf-8')
    sentences = []
    index = 0
    for i in range(0, len(text)):
        if text[i] in Sentence.segmt_list:
            sentences.append(Sentence(text[index:i+1]))
            index = i + 1
    if index != i:
        sentences.append(Sentence(text[index:i]))
    #PRINT(sentences)
    return sentences

def PRINT(s):
    for ss in s:
        print ss.content, ss.weight

def GetPassageTags(passage, keys_factor):
    words = pseg.cut(passage)
    tags = {}
    for word in words:
    #    print word.word, word.flag,
        if tags.get(word.word) != None:
            tags[word.word] += 1
            continue
        if elmn_dict.get(word.flag) != None:
            if elmn_dict[word.flag] != 0: #'0':
                tags[word.word] = 1
        elif elmn_dict[word.flag[0]+'*'] != 0: #'0':
            tags[word.word] = 1
    #print '____________',
    #for w in GetListFromDict(tags, lambda x,y: x[1]>y[1], keys_factor):
    #    print w,
    #print '____________',
    return GetListFromDict(tags, lambda x,y: x[1]>y[1], keys_factor)

def GetListFromDict(tags, func, keys_factor = 1):
    lst = []
    for k, v in tags.iteritems():
        lst.append([k, v])
    lst.sort(func)
    return [tag[0] for tag in lst[0:max(1, int(round(len(lst)*keys_factor)))]]

