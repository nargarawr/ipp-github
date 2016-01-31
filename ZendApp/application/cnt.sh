#/usr/bin/env bash

calc() {
	cd "/var/www/ipp-github/ZendApp/application"
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
}

calc

echo "sum"
calc | grep -o '[[:digit:]]*' | paste -sd+ - | bc
