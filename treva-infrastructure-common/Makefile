all:

install:
	mkdir -p -m 755 $(DESTDIR)/etc/treva-infrastructure
	install -m 600 config/common.conf $(DESTDIR)/etc/treva-infrastructure
	mkdir -p -m 755 $(DESTDIR)$(PREFIX)/share/treva-infrastructure-common
	install -m 600 keys/treva.pem $(DESTDIR)$(PREFIX)/share/treva-infrastructure-common
	install -m 644 config/openssl.conf $(DESTDIR)$(PREFIX)/share/treva-infrastructure-common

clean:

.PHONY: all install clean
