# basicfuncs.py
import math
import os
import time

def ADtimeToTimestamp(time_str, time_format, length = 10):
    if time_str == None:
        return None
    return str(time.mktime(time.strptime(time_str, time_format)))[:length]

def CopyFile(source_file, target_dir):
    os.system("cp %s %s" % (source_file, target_dir))

def CosinSimilarity(vector1, vector2):
    if len(vector1) != len(vector2):
        print "Error: vector1:" + vector1 + " and vector2: " + vector2 + "have different dimensions" 
        return None

    numerator = 0.0
    v1_square = 0.0
    v2_square = 0.0
    for i in range(0, len(vector1)):
        numerator += vector1[i] * vector2[i]
        v1_square += vector1[i] * vector1[i]
        v2_square += vector2[i] * vector2[i]
    denominator = math.sqrt(v1_square * v2_square)
    if denominator == 0:
        return None
    else:
        return numerator / denominator

def CosinSimilarityForDict(dict1, dict2):
    if len(dict1.keys()) != len(dict2.keys()):
        print "Error: dict1: " + dict1.keys() + " and dict2: " + dict2.keys() + "have different key numbers"
        return None
    vector1 = []
    vector2 = []
    for key in dict1.keys():
        if not dict2.has_key(key):
            print "Error: key: " + key + " not exists in dict2"
            return None
        vector1.append(dict1[key])
        vector2.append(dict2[key])
    return CosinSimilarity(vector1, vector2)

def GetTimestamp(length):
    return str(time.time())[:length]

def IsDirectory(dir_path):
    if (not os.path.exists(dir_path)) or os.path.isfile(dir_path):
        return False
    else:
        return True

def IsFile(file_path):
    if not (os.path.exists(file_path) and os.path.isfile(file_path)):
        return False
    else:
        return True

def MakeDirectory(dir_path):
    if not IsDirectory(dir_path):
        os.makedirs(dir_path)

def TimestampToADtime(timestamp, time_format = '%Y-%m-%d %H:%M:%S'):
    return str(time.strftime(time_format, time.localtime(float(timestamp))))

def TrimSpaces(text):
    lst = [u' ', u'\t', u'\n', u'\r']
    front_index = 0
    for i in range(0, len(text)):
        if text[i] in lst:
            front_index += 1
        else:
            break
    text = text[front_index:]
    tail_index = len(text) - 1
    for i in range(tail_index, 0, -1):
        if text[i] in lst:
            tail_index -= 1
        else:
            break
    text = text[0:tail_index]
    return text

def UniqueList(L):
    from sets import Set
    return list(Set(L))

