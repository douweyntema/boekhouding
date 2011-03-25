#!/bin/bash

. /etc/treva-passwd.conf

if [ `id -ru` -eq 0 ]; then
	/usr/sbin/real-passwd "$@"
	exit
fi

username=`whoami`

echo -n "(current) UNIX password: "
read -s oldpass
echo

echo -n "Enter new UNIX password: "
read -s newpass1
echo

echo -n "Retype new UNIX password: "
read -s newpass2
echo

if [ "$newpass1" != "$newpass2" ]; then
	echo "Sorry, passwords do not match"
	exit 1
fi

oldpassurl=`cat <<EOF | tr -d '\n' | od -t x1 -A n | tr ' ' '%'
$oldpass
EOF
`

newpassurl=`cat <<EOF | tr -d '\n' | od -t x1 -A n | tr ' ' '%'
$newpass1
EOF
`

tempfile=`mktemp`
cat <<EOF | tr -d '\n' >$tempfile
username=$username&oldpassword=$oldpassurl&newpassword=$newpassurl
EOF

result=`wget -q -o /dev/null -O- --post-file=$tempfile $apiurl/changepassword.php`

rm -f $tempfile

if [ "$result" = "success" ]; then
	echo "Password changed."
elif [ "$result" = "wrongpassword" ]; then
	echo "Incorrect password."
else
	echo "Internal error changing password."
fi
