# get Makefile directory name: http://stackoverflow.com/a/5982798/376773
THIS_MAKEFILE_PATH:=$(word $(words $(MAKEFILE_LIST)),$(MAKEFILE_LIST))
THIS_DIR:=$(shell cd $(dir $(THIS_MAKEFILE_PATH));pwd)

install:
	@touch $(THIS_DIR)/log
	@chmod og-r $(THIS_DIR)/log
	@echo "Make sure $(THIS_DIR)/log is writeable by the web server process" > /dev/stderr

token:
	@php $(THIS_DIR)/get-token.php
	@$(THIS_DIR)/get-token.sh
	@echo "DONE!"

.PHONE: install token
