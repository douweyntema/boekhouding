#!/bin/bash

PERCENT=$1
FROM=postmaster@treva.nl
if [ "$2" = "customer" ]; then
	TO=$CUSTOMEREMAIL
	msg="From: FROM
To: $TO
Subject: Shared email quota is $PERCENT% full
Content-Type: text/plain; charset=UTF-8

The shared quota for your Treva Technologies mailboxes is over $PERCENT$ full. If it fills up completely, you will not be able to receive incoming mail at any Treva email account.
"
else
	TO=$USER
	msg="From: $FROM
To: $TO
Subject: Your email quota is $PERCENT% full
Content-Type: text/plain; charset=UTF-8

Your mailbox is over $PERCENT% full. If it fills up completely, you will not be able to receive incoming mail. We recommend you clean out your mailbox.
"
fi

echo -e "$msg" | /usr/sbin/sendmail -f "$FROM" "$TO"
echo -e "$msg" | /usr/sbin/sendmail -f "$FROM" "$FROM"

exit 0
