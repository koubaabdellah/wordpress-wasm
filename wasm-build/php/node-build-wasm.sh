#!/bin/bash

set -e

PHP_VERSION=${1:-8.0.24}
if [[ $PHP_VERSION == "7.*" ]]
then
  VRZNO_FLAG="--enable-vrzno"
  EXTRA_EXPORTED_FUNCTIONS=', "_exec_callback", "_del_callback"'
else
  VRZNO_FLAG="--disable-vrzno"
  EXTRA_EXPORTED_FUNCTIONS=""
fi

WITH_LIBXML="yes" # Hardcoded for now, flip to "no" to disable libxml.

EXPORTED_FUNCTIONS='["_pib_init", "_pib_destroy", "_pib_run", "_pib_exec" "_pib_refresh", "_main", "_php_embed_init", "_php_embed_shutdown", "_php_embed_shutdown", "_zend_eval_string" '$EXTRA_EXPORTED_FUNCTIONS']'

docker build . --tag=wasm-wordpress-php-builder \
  --build-arg PHP_VERSION=$PHP_VERSION \
  --build-arg VRZNO_FLAG="$VRZNO_FLAG" \
  --build-arg WITH_LIBXML="$WITH_LIBXML"

if [ "$WITH_LIBXML" = "yes" ]; \
    then export LIBXML="/root/lib/lib/libxml2.a"; \
    else export LIBXML=""; \
    fi 

docker run \
        -v `pwd`/preload:/preload \
        -v `pwd`/docker-output:/output \
        wasm-wordpress-php-builder:latest \
        emcc -O3 \
        -o /output/node-php.js \
        --llvm-lto 2                     \
        -s EXPORTED_FUNCTIONS="$EXPORTED_FUNCTIONS" \
        -s EXTRA_EXPORTED_RUNTIME_METHODS='["ccall", "UTF8ToString", "lengthBytesUTF8"]' \
        -s MAXIMUM_MEMORY=-1             \
        -s INITIAL_MEMORY=1024MB \
        -s ALLOW_MEMORY_GROWTH=1         \
        -s ASSERTIONS=0                  \
        -s ERROR_ON_UNDEFINED_SYMBOLS=0  \
        -s EXPORT_NAME="'PHP'"           \
        -s MODULARIZE=1                  \
        -s INVOKE_RUN=0                  \
        -s USE_ZLIB=1                    \
                /root/lib/pib_eval.o /root/lib/libphp7.a $LIBXML \
        -lnodefs.js \
        --pre-js /preload/node-pre.js \
        -s ENVIRONMENT=node

