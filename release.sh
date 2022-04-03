#!/bin/bash
# Create a release on GitHub.
# Extracts the most recent changelog section from readme.txt
# if readme.txt is in standard WordPress format.
# Also makes a separate backup of the whole thing including hidden files and phpunit tests.
# Prerequisite: Main plugin file slug must be the same as the plugin folder name.
# Prerequisite: Existing git repo with its remote origin set up on GitHub. Both repo names must match the plugin slug, exactly.
# Configure the first few variables.
 
set -e
 
SLUG=${PWD##*/}
CURRENTDIR=`pwd`
MAINFILE="${SLUG}.php"

if [ -n "$(git status --porcelain)" ]; then
  echo "*********************************";
  echo "Please commit your changes first.";
  echo "*********************************";
  git status --porcelain;
  exit 1;
fi

# Get version from main plugin file
NEWVERSION=`grep "^Version" "$CURRENTDIR/${SLUG}.php" | awk -F' ' '{print $2}' | sed 's/[[:space:]]//g'`
if [[ -z "$NEWVERSION" ]]; then echo "ERROR: Cannot find version. Exiting early...."; exit 1; fi
GITHUBVERSION=`gh release list --limit 1 | awk -F' ' '{print $1}'`

echo "****************************************";

echo "Found version: $NEWVERSION"
echo "GitHub version: $GITHUBVERSION"
if [ "$GITHUBVERSION" == "$NEWVERSION" ]; then
  echo "Version in PHP file has to be different."
  echo "Exiting....";
  EXIT=1
fi

echo "****************************************";
if [ $EXIT ]; then exit 1; fi

 
ZIPFILE=${SLUG}-${NEWVERSION}
echo "Creating temporary zip file ${ZIPFILE}.zip..."
composer clear
composer readme
composer archive --format=zip --file ${ZIPFILE}
zip -u ${ZIPFILE}.zip vendor/*
zip -d ${ZIPFILE}.zip README.md phpunit.xml .gitignore release.sh composer.* 'tests/*'

# Create a Release on GitHub
echo "Creating a new release on GitHub"
# Get changelog text from readme
gh release create ${NEWVERSION} ${ZIPFILE}.zip

echo "Deleting temporary zip file."
rm ${ZIPFILE}.zip
 
echo "*** FIN ***"
