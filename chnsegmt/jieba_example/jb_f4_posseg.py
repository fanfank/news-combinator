#encoding=utf-8
import jieba.posseg as pseg
sentence = "我爱广州中山大学"
sentence = unicode(sentence, 'utf-8')
while sentence != "q":
    words = pseg.cut(sentence)
    for w in words:
        print w.word, w.flag
    sentence = raw_input("输入任何句子（'q'退出）：")

