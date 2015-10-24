#!/usr/bin/env bash
echo "php"
find -name '*.php' | xargs wc -l | grep "total"

echo "phtml"
find -name '*.phtml' | xargs wc -l | grep "total"

cd ../public/js/
echo "js"
find -name '*.js' | xargs wc -l | grep "total"

cd ../css/
echo "css"
find -name '*.css' | xargs wc -l | grep "total"
