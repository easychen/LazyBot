#!/bin/sh
GIT_WORK_TREE=[path] git checkout -f
chown www-data:www-data -R [path]