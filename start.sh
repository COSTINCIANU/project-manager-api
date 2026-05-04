#!/bin/bash
echo "Starting server on port 8080"
php -S 0.0.0.0:8080 -t public/ public/index.php
