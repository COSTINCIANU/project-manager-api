#!/bin/bash
PORT=${PORT:-8000}
echo "Starting server on port $PORT"
php -S 0.0.0.0:$PORT -t public/ public/index.php
