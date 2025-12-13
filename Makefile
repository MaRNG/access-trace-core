clean-temp:
	- rm -rf var/temp/*

clean-log:
	- rm -rf var/log/*

clean: clean-temp clean-log