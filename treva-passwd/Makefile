all:

install:
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/bin
	install -m 755 src/passwd.sh $(DESTDIR)$(PREFIX)/bin/treva-passwd
	install -m 755 src/chsh.sh $(DESTDIR)$(PREFIX)/bin/treva-chsh
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/sbin
	install -m 755 src/update-treva-passwd.php $(DESTDIR)$(PREFIX)/sbin/update-treva-passwd
	mkdir -p -m 755 $(DESTDIR)/etc/treva-infrastructure
	install -m 644 config/treva-passwd.conf $(DESTDIR)/etc/treva-infrastructure/passwd.conf
	mkdir -p -m 755 $(DESTDIR)/etc/cron.d
	install -m 644 config/cron $(DESTDIR)/etc/cron.d/update-treva-passwd

clean:

.PHONY: all install clean
