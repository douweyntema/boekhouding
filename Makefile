translation:
	xgettext --language=Python --from-code=UTF-8 --keyword=_ --keyword -o locale/templates/boekhouding.pot src/*.php src/*/*.php
	msgfmt -o locale/nl_NL/LC_MESSAGES/boekhouding.mo locale/nl_NL/LC_MESSAGES/boekhouding.po

all: translation