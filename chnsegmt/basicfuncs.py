# basicfuncs.py
import math
import os

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

