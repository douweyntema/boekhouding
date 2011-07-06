#!/bin/bash

. /etc/treva-passwd.conf

if [ `id -ru` -eq 0 ]; then
	/usr/sbin/real-chsh "$@"
	exit
fi

username=`whoami`

echo -n "Password: "
read -s password

echo "Changing the login shell for user $username"

oldshell=`cat /etc/passwd | grep "^$username:" | sed -e 's/.*://'`

echo "Enter the new value, or press ENTER for the default"
echo -ne "	Login shell [$oldshell]: "

read newshell

if [ -z "$newshell" ]; then
	exit
fi

passwordurl=`cat <<EOF | tr -d '\n' | od -t x1 -A n | tr ' ' '%'
$password
EOF
`

newshellurl=`cat <<EOF | tr -d '\n' | od -t x1 -A n | tr ' ' '%'
$newshell
EOF
`

tempfile=`mktemp`
cat <<EOF | tr -d '\n' >$tempfile
username=$username&password=$passwordurl&shell=$newshellurl
EOF

result=`wget -q -o /dev/null -O- --post-file=$tempfile $apiurl/changeshell.php`

rm -f $tempfile

if [ "$result" = "success" ]; then
	true
elif [ "$result" = "wrongpassword" ]; then
	echo "Incorrect password."
elif [ "$result" = "invalidshell" ]; then
	echo "$newshell is an invalid shell."
else
	echo "Internal error changing shell."
fi
