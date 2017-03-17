#!/bin/bash

for tar in *.tar.gz
do
    tar -zxvf $tar
    base=$(basename $tar)
    zip -j ${base:0:10}.zip ${base:0:10}/*.*
    rm -R ${base:0:10}
done
