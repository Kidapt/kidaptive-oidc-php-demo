# Kidaptive OpenID Connect PHP Demo
The purpose of this project is to demonstrate how to add OIDC functionality to an existing database to allow users to
authenticate with the Adaptive Learning Platform (ALP) using their existing credentials

## Dependencies
* PHP 7.1 - May work with other versions
* Git
* Bash
* Python - testing only

## Setup
Run `git submodule update --init` to pull all submodules.

## Demo
This repository includes a SQLite database that represents an existing authentication system. It contains a user table
with two users and a learner table with two learners. You start the SQLite console with `sqlite3 demo_database.sqlite`.
`.tables` will list all the tables in this database. Note that there are only the `user_info` and `learner_info` tables
in this database. `.exit` quits the console.

This goal of this section is to show how to add OIDC functionality to this database so that these users can log into ALP
using their existing credentials. If you wish to work directly on your own database, please skip ahead to the next
[section](#db_int).

### Setting up the database
Run `. init_db.sh` to create the necessary tables in the database, generate client/secret, and generate a RSA key pair. You
will notice that two files are generated. `clientInfo.properties` holds the generated client_id and client_secret, and `key.pub`
contains the public key used to verify the generated ID tokens. Normally these two files would be given to ALP so that it
knows how to communicate with your server. In addition, `.tables` from the SQLite console shows that several `oauth_`
tables have been added. These are used to store various information during the OIDC flow.

## <a name="db_int"></a>Integrating your database
By replacing the SQLite database with your own database, you can add OIDC functionality to your own database.

### Changing the PHP files
#### server.php


#### Storage.php

## <a name="test"></a>Testing your configuration
### Starting the PHP standalone server
Run `php -S localhost:8000`. This starts a standalone PHP server that listens on port 8000 on localhost. `localhost:8000`
can be replaced with any address/port that resolves to your server.

### cURLing the endpoints
#### Authentication endpoint
This endpoint checks that the user's credentials are valid.
```bash
auth_endpoint=localhost:8000/authorize.php &&
token_endpoint=localhost:8000/token.php &&
info_endpoint=localhost:8000/userInfo.php &&

username=user1 &&
password=password1 &&

state=$(php -r 'echo base64_encode(random_bytes(33));') &&
nonce=$(php -r 'echo base64_encode(random_bytes(33));') &&
. clientInfo.properties &&
redirect_uri=https://develop.kidaptive.com/v3/openid/user/ajax-cb &&
response_type=code &&
scope="'openid offline_access'" &&

data=$(echo username password state nonce client_id redirect_uri response_type scope | xargs -n1 | awk '{print "--data-urlencode "$1"=$"$1}') &&
eval eval curl -s -X POST -D /dev/stdout $auth_endpoint $data | grep Location
```