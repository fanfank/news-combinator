import sys
from optparse import OptionParser

USAGE = "usage: python test.py [any args] -a [any args] -b [any args] -c [any args] [any args]"

parser = OptionParser(USAGE)
parser.add_option("-a", dest="a")
parser.add_option("-b", dest="b")
parser.add_option("-c", dest="c")

print parser.parse_args()
opt, args = parser.parse_args()

if len(args) < 2:
    print USAGE
    sys.exit(1)

print opt,"\n"
print args, "\n"
