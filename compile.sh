#!/bin/sh

#mkdir "bin"  > /dev/null 2>&1
#rm ./bin/*.php  > /dev/null 2>&1
#for f in *.php ; do
# if [ -f $f ] ; then
#    php -w $f > ./bin/$f
#    echo "minifie $f"
#  fi ;
# done

f=fastIce.php
n=${f%\.*}
echo "minifie $f to $n.min.php"
php -w $f > ./$n.min.php