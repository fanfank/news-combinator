import json, os, sys, codecs
from getabstract import GetPassageAbstract
f = open('test/000913.json', 'r')
js = json.load(f)
passage = js['contents']['passage']
print GetPassageAbstract(passage, 0.4, 0.3)

