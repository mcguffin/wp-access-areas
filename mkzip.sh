CWDNAME=${PWD##*/}

if [ "$CWDNAME" == "trunk" ]; then
	parent=${PWD%/*}
	CWDNAME=${parent##*/}
fi
#find /$CWDNAME -path '*/.*' -prune -o -type f -print | zip ../$CWDNAME.zip -@
find . -path '*/.*' -prune -o -type f \( ! -iname 'README.md'  ! -iname 'mkzip.sh' \) -print | zip ../$CWDNAME.zip -@