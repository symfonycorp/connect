#!/bin/bash -x

DIRECTORY="tests/fixtures"

curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/ > $DIRECTORY/root.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/badges > $DIRECTORY/badges.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/clubs > $DIRECTORY/clubs.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/clubs/4c147ff8-2e0e-4fd5-b833-aa0cd5fa486f > $DIRECTORY/club.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/projects > $DIRECTORY/projects.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/projects/454f1e7a-57d3-4240-a1c1-a3875ec8bc89 > $DIRECTORY/project.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/users > $DIRECTORY/users.xml
curl -H "Accept: application/vnd.com.symfony.connect+xml" https://connect.symfony.com/api/users/aa5e22b0-6189-4113-9c68-91d4a3c32b7c > $DIRECTORY/user.xml
