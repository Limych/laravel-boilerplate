#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.

git config --global core.excludesfile ~/.gitignore
echo .idea/ >~/.gitignore

cd ~/code
git config --global core.eol lf
git config --global core.autocrlf input
git config core.eol lf
git config core.autocrlf input
