#!/bin/bash

echo $(date +"%Y%m%d")

mysqldump --add-drop-table allpds3data > allpds3data_$(date _"%Y%m%d").sql
mysql $1 < allpds3data_$(date _"%Y%m%d").sql
