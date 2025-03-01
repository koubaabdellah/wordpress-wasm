#!/bin/bash

# set -e

cd preload

# Remove previous WordPress installation
rm -rf wordpress
rm -rf wordpress-static

# Download specific version of WordPress
wp_zip_url=${1:-${WP_URL:-https://wordpress.org/wordpress-6.0.2.zip}}
wget -O wp.zip $wp_zip_url
unzip wp.zip
rm wp.zip

# Patch WordPress with sqlite support
# https://github.com/aaemnnosttv/wp-sqlite-integration
# https://github.com/aaemnnosttv/wp-sqlite-db
curl https://raw.githubusercontent.com/aaemnnosttv/wp-sqlite-db/master/src/db.php \
   | sed 's/$exploded_parts = $values_data;/$exploded_parts = array( $values_data );/g' \
   > wordpress/wp-content/db.php

# Prepare WordPress static files
cp -r wordpress wordpress-static
cd wordpress-static
find ./ -name '*.php' | xargs rm
cd ..

# Install WordPress
cd wordpress

# Remove non-default themes
rm -r wp-content/themes/twentytwenty wp-content/themes/twentytwentyone

# Remove unused static files
find ./ -type f -name '*.eot' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.gif' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.htaccess' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.md' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.mp4' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.png' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.scss' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.stylelintignore' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.svg' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.ttf' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.txt' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.woff' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.wof2' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.jpeg' | xargs rm -r 2> /dev/null
find ./ -type f -name '*.jpg' | xargs rm -r 2> /dev/null

echo 'function getLazyFiles() { var sa = []; ' > ../wp-lazy-files.js

if [ "$LAZY_FILES" == "true" ]; then
    
    # load-styles.php reads the CSS files from the disk and concats them.
    # However, with SCRIPT_DEBUG=false, it reads only the minified files.
    # Therefore, we can remove the unminified CSS files when a minified version is available.
    find ./ -type f -name '*.min.css' | sed 's/\.min\.css$/.css/g' | xargs rm 2> /dev/null

    # Let's load all the other CSS files lazily instead of preloading them with the initial data bundle.
    for match in $(find . -type f -name '*.css' ); do
        # match is something like ./wp-includes/css/dist/block-library/style.css

        # filename is style.css
        filename=$(echo $match | awk -F'/' '{print $NF}');

        # filepath is /wp-includes/css/dist/block-library
        filepath=$(echo ${match:1} | rev | cut -d '/' -f 2- | rev);

        filesize=$(wc -c $match | awk '{print $1}');

        echo "sa.push( [ '/preload/wordpress/$filepath', '$filename', '$filepath/$filename', $filesize ] );" >> ../wp-lazy-files.js
    done;

    find ./ -type f -name '*.css' | xargs rm 2> /dev/null

    # Same as above, but for JS and load-scripts.php
    find ./ -type f -name '*.min.js' | sed 's/\.min\.js$/.js/g' | xargs rm 2> /dev/null

    # Let's load all the other JS files lazily instead of preloading them with the initial data bundle.
    for match in $(find . -type f -name '*.js' ); do
        # match is something like ./wp-includes/js/dist/block-library/script.js

        # filename is script.js
        filename=$(echo $match | awk -F'/' '{print $NF}')

        # filepath is /wp-includes/js/dist/block-library
        filepath=$(echo ${match:1} | rev | cut -d '/' -f 2- | rev)

        echo "sa.push( [ '/preload/wordpress$filepath', '$filename', '$filepath/$filename' ] );" >> ../wp-lazy-files.js
    done

    find ./ -type f -name '*.js' | xargs rm 2> /dev/null
fi

echo "
return sa.map( function( a ) {
    return {
        path: a[0],
        filename: a[1],
        fullPath: a[2],
        size: a[3],
    };
} );
}" >> ../wp-lazy-files.js

# Remove whitespace from PHP files
for phpfile in $(find ./ -type f -name '*.php'); do
    php -w $phpfile > $phpfile.small
    mv $phpfile.small $phpfile
done

# Let the WordPress installer do its magic
cp wp-config-sample.php wp-config.php # Required by the drop-in SQLite integration plugin
php -S 127.0.0.1:8000&
sleep 6
http_response=$(curl -o ./debug.txt -s -w "%{http_code}\n" -XPOST http://127.0.0.1:8000/wp-admin/install.php\?step\=2 --data "language=en&prefix=wp_&weblog_title=My WordPress Website&user_name=admin&admin_password=password&admin_password2=password&Submit=Install WordPress&pw_weak=1&admin_email=admin@localhost.com")
pkill php
if [ $http_response != "200" ]; then
    exit 'WordPress installation failed'
    cat debug.txt
fi
