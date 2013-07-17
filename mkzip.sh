CWDNAME=${PWD##*/}
#find /$CWDNAME -path '*/.*' -prune -o -type f -print | zip ../$CWDNAME.zip -@
find . -path '*/.*' -prune -o -type f \( ! -iname 'README.md'  ! -iname 'mkzip.sh' \) -print | zip ../$CWDNAME.zip -@