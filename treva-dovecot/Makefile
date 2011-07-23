all:

install:
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/sbin
	install -m 755 src/update-treva-dovecot.php $(DESTDIR)$(PREFIX)/sbin/update-treva-dovecot
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/lib/treva-dovecot
	install -m 755 src/quota_warning.sh $(DESTDIR)$(PREFIX)/lib/treva/dovecot/
	mkdir -p -m 755 $(DESTDIR)/usr/share/treva-dovecot
	install -m 644 src/dovecot.conf $(DESTDIR)/usr/share/treva-dovecot/
	mkdir -p -m 755 $(DESTDIR)/etc/cron.d
	install -m 644 config/cron $(DESTDIR)/etc/cron.d/update-treva-dovecot

clean:

.PHONY: all install clean
