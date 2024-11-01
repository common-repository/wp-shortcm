build:
	rsync -av --update --delete . _build --exclude=.git --exclude=_build --exclude=*.zip
	rm -f wp-shortcm.zip
	cd _build && zip -r ../wp-shortcm.zip *
	rsync -av --update --delete _build/* ../wp-shortcm/trunk --exclude=assets
clean:
	rm -rf _build
