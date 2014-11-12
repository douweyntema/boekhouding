mo:
	msgfmt -o locale/nl_NL/LC_MESSAGES/boekhouding.mo locale/nl_NL/LC_MESSAGES/boekhouding.po

pot:
	xgettext --language=Python --from-code=UTF-8 --keyword=_ --keyword -o locale/templates/boekhouding.pot src/*.php src/*/*.php

translation: pot mo

all: mo
