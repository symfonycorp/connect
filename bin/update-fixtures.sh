#!/bin/bash -x

DIRECTORY="tests/fixtures"

curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/ > $DIRECTORY/root.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/badges > $DIRECTORY/badges.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/users > $DIRECTORY/users.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/users/aa5e22b0-6189-4113-9c68-91d4a3c32b7c > $DIRECTORY/user.xml
