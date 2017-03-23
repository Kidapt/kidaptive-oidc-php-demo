# Kidaptive OpenID Connect PHP Demo
The purpose of this project is to demonstrate how to add OIDC functionality to an existing database to allow users to
authenticate with the Adaptive Learning Platform (ALP) using their existing credentials.

## Dependencies
* PHP 7.1 - May work with other versions
* Git
* Bash

## Setup
Run `git submodule update --init` to pull all submodules.

## Demo
This repository includes a SQLite database that represents an existing authentication system. It contains a user table
with two users and a learner table with two learners. You can start the SQLite console with `sqlite3 demo_database.sqlite`.
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

Now `authorize.php`, `token.php`, and `userInfo.php` are set up to accept authorization, token, and userInfo requests.

### <a name="test"></a>Testing your configuration
Run `php -S localhost:8000`. This starts a standalone PHP server that listens on port 8000 on localhost. Run `sh test.sh`
to run the test. If everything is set up correctly, you should see output similar to this:
```text
Testing authentication...
Success!
Testing token...
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJsb2NhbGhvc3QiLCJzdWIiOiI3MjUyMTMxMjU2MTA3MDQ3ODc2IiwiYXVkIjoiSVo0TXRpTm9
hWmtnIiwiaWF0IjoxNDkwMjk1ODYzLCJleHAiOjE0OTAyOTk0NjMsImF1dGhfdGltZSI6MTQ5MDI5NTg2Mywibm9uY2UiOiJENnh6SnF6b09ZSllQbWE1RGl
3TTRcL3B0OFc2cURiXC92dkxmcnJXb1JEdUVkIn0.v1YUtRWJP_tWK7CqoHsiOs4fkvnY4r0unUrt1F7oyKLUvCYs-3zGmtq5OSGGZDS0zuhIFlWPWPGPvdw
5wRMgvSF68k2URZOjCUxn_DrtvyXKS6yLdHFzDleF6qNc_-glwzIOPDXUUuU1EPEHvW5fhE3jua0XGz2WR5p41HDKgTuaNGCZmnlVdDbkppLO8wFeyQAHXyZ
yHYaUULDUuRpizpPx87MCa0oCCiemtwqie2hNXVYVVne1gOWyQVzRi_F3yp3RiPss6i3O3WiOGS0O7wGt9bGW1IN3zmPAIqZ_LhqOUeR2_6yT_bBTNvatQkR
-crrldgVSx-IMqVjGaZUFCg
Success!
Test userInfo...
{"name":"user1_name","sub":"7252131256107047876","preferences":{"user_prop_1":"user1_prop1","user_prop_2":"user1_prop2"}
,"learners":[{"sub":"5627442102899668490","name":"learner1","gender":"decline","birthdate":"2009-04-01","preferences":{"
learner_prop_1":"learner1_prop1","learner_prop_2":"learner1_prop2"}},{"sub":"7782722190966141704","name":"learner2","gen
der":"male","birthdate":"2008-05-01","preferences":{"learner_prop_1":"learner2_prop1","learner_prop_2":"learner2_prop2"}
}]}
Success!
```
The strings under `Testing token...` and `Testing userInfo...` are the ID token and user/learner info respectively. You
can view the contents of the ID Token with [this tool](https://jwt.io) and the signature can be verified by pasting the 
contents of `key.pub` into the appropriate field.

## <a name="db_int"></a>Integrating your database
By replacing the SQLite database with your own database, you can add OIDC functionality to your own database.

### Changing the PHP files
You will need to change some of the PHP files to work with your database:
#### server.php
The `$config` variable in this file contains a non-exhaustive list of options to customize the behavior OIDC. The most
important property to change is `issuer`: this should be the host name for your authentication server and will be included in
the ID token returned by your token endpoint.

#### Storage.php
This file handles communication with your database.

##### $connection
This array contains the connection information for your database. You should change the `dsn`, `username`, and `password`
values to be the correct values for your database.

##### checkCredentials
This function should be changed to return true if a username/password combination is valid, and false otherwise.

##### getUserId
This function should return a unique and consistent identifier each username, or false if the username does not exist.

##### getUser
You should modify the SQL statement in this function to return a single row representing the queried user. If you return
a column named `sub`, it must contain the same userId that was queried. A column containing the user's name should be 
aliased to `name`. All other columns will be put in the `preferences` object. This method will also call the `getLearnerInfo`
method to populate the `learners` object.

##### getLearnerInfo
You should modify the SQL statement in this function to return one row for each learner associated with the queried user.
The result set must contain a `sub` column, which contains a unique and consistent identifier for each learner. Columns
containing the name, gender, and birthday of the learner should be aliased to `name`, `gender`, and `birthdate` respectively.
All other columns will be put in the `preferences` object.

### Finishing touches
Run `. init_db` to create the new database tables, generate client ID and secret, and generate an RSA key pair. Then send
the following information to Kidaptive so they can be added to ALP:
1. Generated `client.info` file
2. Generated `key.pub` file
3. The hostname for your server
4. The URLs for the authorization, token, and userInfo endpoints if you've changed them

You can test your configuration by changing username, password, and endpoint variables in `test.sh`.