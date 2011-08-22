#!/bin/bash
#
# This script installs and initializes ATK14 in working directory.
# Also creates required databases and roles in Postgresql, configures and restarts Apache.
#
# Root's rights are required for some operations.
# So you'll be asked for your sudo password.
#
# Works on Ubuntu 10.10
#
# Usage:
#  $ mkdir myapp
#  $ cp myapp
#  $ atk14_init.sh

random_string () {
  MATRIX="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
  LENGTH=$1

  while [ "${n:=1}" -le "$LENGTH" ]
  do
    PASS="$PASS${MATRIX:$(($RANDOM%${#MATRIX})):1}"
    let n+=1
  done

  echo $PASS
}

# we need current working directory to be empty
files_in_current_directory=`ls -a | wc -l`
if [ $files_in_current_directory != "2" ]; then
  echo "Current working dir is not empty!"
  exit 1
fi

# setting up some initials
PWD=`pwd`                                 # /home/joe/webapps/myapp
APPNAME=`basename "$PWD"`                 # myapp
PG_USER=postgres                          # Postgresql's service account name
DBNAME_DEVEL="${APPNAME}_devel"           # myapp_devel
DBNAME_TEST="${APPNAME}_test"             # myapp_test
PG_PASSWORD=`random_string 6`
SECRET_TOKEN=`random_string 64`

echo "Your new ATK14 app will be >> $APPNAME <<"
echo "Two new databases will be created in Postgresql: $DBNAME_DEVEL and $DBNAME_TEST"
echo "Two new users will be created in Postgresql: $DBNAME_DEVEL and $DBNAME_TEST"
echo "New virtual host will be configured in Apache: $APPNAME.localhost"
echo "New record will be added to /etc/hosts: 127.0.0.1 $APPNAME.localhost"
echo "And even more you will be asked for your sudo password!"
echo -n "Sounds good to you? (press y if so) "
read confirm
if [ "$confirm" != "y" ]; then
  exit 1
fi

echo "Gonna create databases & users"
echo "You may be asked for your password"

sql=$( cat <<EOF
-- creating devel database & user
CREATE DATABASE $DBNAME_DEVEL; 
CREATE USER $DBNAME_DEVEL WITH ENCRYPTED PASSWORD '$PG_PASSWORD';
-- creating test database & user
CREATE DATABASE $DBNAME_TEST;
CREATE USER $DBNAME_TEST WITH ENCRYPTED PASSWORD '$PG_PASSWORD';
EOF
)

echo "Gonna execute the following SQL as user $PG_USER in database template1:"
echo ""
echo "$sql"
echo ""

sudo -u $PG_USER -s "echo \"$sql\" | psql template1"

echo "Gonna copy ATK14 from repository";

git clone --recursive git://github.com/yarri/Atk14.git ./atk14 &&
mv atk14/skelet/.htaccess ./ &&\
mv atk14/skelet/* ./ &&\
rmdir atk14/skelet

# removing git's working files and dirs
find ./ -name '.git*' -type f -exec rm {} \;
find ./ -name '.git' -type d -exec rm -rf {} \;

chmod -R 777 ./tmp
touch ./log/application.log
chmod 666 ./log/application.log
touch ./log/test.log
chmod 666 ./log/test.log

sed -i "s/password: p/password: $PG_PASSWORD/" config/database.yml
sed -i "s/atk14_devel/$DBNAME_DEVEL/" config/database.yml
sed -i "s/atk14_test/$DBNAME_TEST/" config/database.yml

ATK14_ENV=DEVELOPMENT ./scripts/initialize_database
ATK14_ENV=TEST        ./scripts/initialize_database

ATK14_ENV=DEVELOPMENT ./scripts/migrate
ATK14_ENV=TEST        ./scripts/migrate

sed -i "s/put_some_random_string_here/$SECRET_TOKEN/" config/local_settings.inc
sed -i "s/myapp.localhost/$APPNAME.localhost/" config/local_settings.inc
sed -i "s/www.myapp.com/www.$APPNAME.com/" config/local_settings.inc

# adding server name in /etc/hosts
sudo -s "echo '' >> /etc/hosts"
sudo -s "echo '# added by atk14_init.sh' >> /etc/hosts"
sudo -s "echo '127.0.0.1 $APPNAME.localhost' >> /etc/hosts"

# configuring apache
sudo -s "cat <<EOF > /etc/apache2/sites-available/$APPNAME.localhost
<VirtualHost *:80>
  DocumentRoot $PWD
  ServerName $APPNAME.localhost
  Options FollowSymLinks
  <Directory $PWD>
  AllowOverride All
  </Directory>
</VirtualHost>
EOF"
sudo ln -s /etc/apache2/sites-available/$APPNAME.localhost /etc/apache2/sites-enabled/
sudo /etc/init.d/apache2 restart

echo 
echo "You are advised to add these lines to ~/.pgpass"
ATK14_ENV=DEVELOPMENT ./scripts/pgpass_record
ATK14_ENV=TEST ./scripts/pgpass_record

echo 
echo "Now try this address in your browser:"
echo "http://$APPNAME.localhost/"

echo
echo "Happy coding"
