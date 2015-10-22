#!/usr/bin/env bash
find -name '*.php' | xargs wc -l
find -name '*.phtml' | xargs wc -l

cd ../public/js/
find -name '*.js' | xargs wc -l

cd ../css/
find -name '*.css' | xargs wc -l