all:

install:
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/sbin
	install -m 755 src/update-treva-bind.php $(DESTDIR)$(PREFIX)/sbin/update-treva-bind
	mkdir -p -m 755 $(DESTDIR)/etc/cron.d
	install -m 644 config/cron $(DESTDIR)/etc/cron.d/update-treva-bind

clean:

.PHONY: all install clean
