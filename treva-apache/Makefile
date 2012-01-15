all:

install:
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/sbin
	install -m 755 src/update-treva-apache.php $(DESTDIR)$(PREFIX)/sbin/update-treva-apache
	mkdir -p -m 755 $(DESTDIR)/etc/treva-infrastructure
	install -m 700 config/apache.conf $(DESTDIR)/etc/treva-infrastructure
	mkdir -p -m 755 $(DESTDIR)/etc/cron.d
	install -m 644 config/cron $(DESTDIR)/etc/cron.d/update-treva-apache
	mkdir -p -m 755 $(DESTDIR)/etc/logrotate.d
	install -m 644 config/logrotate $(DESTDIR)/etc/logrotate.d/treva-apache
	mkdir -p -m 755 $(DESTDIR)/etc/apache2/conf.d
	install -m 644 config/sites-scripted $(DESTDIR)/etc/apache2/conf.d
	mkdir -p -m 755 $(DESTDIR)/etc/apache2/mods-enabled
	ln -s ../mods-available/rewrite.load $(DESTDIR)/etc/apache2/mods-enabled
	mkdir -p -m 755 $(DESTDIR)/etc/apache2/sites-scripted
	mkdir -p -m 755 $(DESTDIR)${PREFIX}/share/treva-apache
	install -m 644 config/suphp.conf $(DESTDIR)${PREFIX}/share/treva-apache

clean:

.PHONY: all install clean
