#encoding=utf-8
import jieba

seg_list = jieba.cut("我来到广州中山大学", cut_all=True)
print "Full Mode:", "/".join(seg_list) # 全模式

seg_list = jieba.cut("我来到广州中山大学", cut_all=False)
print "Default Mode:", "/".join(seg_list) # 精确模式

seg_list = jieba.cut("他来到了北京百度大厦") # 默认是精确模式
print ",".join(seg_list)

seg_list = jieba.cut_for_search("小明硕士毕业于中国广州中山大学，后在美国麻省深造") # 搜索引擎模式
print ",".join(seg_list)
