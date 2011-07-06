all:

install:
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/bin
	install -m 755 src/update-http $(DESTDIR)$(PREFIX)/bin
	mkdir -p -m 755 $(DESTDIR)/etc/treva-apache
	install -m 700 src/update-http.conf.php $(DESTDIR)/etc/treva-apache

clean:

.PHONY: all install clean
