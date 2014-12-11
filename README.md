geotime
=======
Kingdoms though time.


###Requirements

* *nix
* MongoDB
* NodeJS and NPM
* Apache+PHP 5 with MongoDB extension


###Installation

* Clone the repo, ```cd``` inside of it.
* Install Composer : 
```bash
$ curl -sS https://getcomposer.org/installer | php
```
* Install Composer project dependencies : 
```bash
$ php composer.phar install
```

* Install Bower project dependencies :
```bash
$ npm install -g bower
$ bower install
```

* (Optional) If you want to test the application, install the karma packages as well :
```bash
$ npm install karma karma-cli karma-jasmine karma-jasmine-jquery karma-jasmine-matchers karma-phantomjs-launcher karma-coverage karma-junit-reporter
```

* Set up some rights : 
```bash
$ chmod -R +w test/geotime/cache
$ chmod -R +w cache
```
* Set up the rights for the normal and test DBs, using the admin user : 
```bash
$ mongo
MongoDB shell version: 2.4.9
connecting to: test
> db.auth("admin","myadminpassword")
1
> use geotime
switched to db geotime
> db.addUser({user: "mydbusername", pwd: "mydbpassword", "roles": ["readWrite", "dbAdmin"]})
{
        "user" : "mydbusername",
        "pwd" : "efd1d99dce309152fed1f152572b7735",
        "roles" : [
                "readWrite",
                "dbAdmin"
        ],
        "_id" : ObjectId("5333ff533102c9b9d51a62a3")
}
> use geotime_test
switched to db geotime_test
> db.addUser({user: "mydbusername", pwd: "mydbpassword", "roles": ["readWrite", "dbAdmin"]})
{
        "user" : "mydbusername",
        "pwd" : "efd1d99dce309152fed1f152572b7735",
        "roles" : [
                "readWrite",
                "dbAdmin"
        ],
        "_id" : ObjectId("5333ff533102c9b9d51a62a3")
}
```
* Set up the DB connection  : create a file shaped like :
```ini
username=mydbusername
password=mydbpassword
```
and specify its path in [lib/geotime/Database.php](lib/geotime/Database.php) : these credentials will be used for the DB connection.

* Set up the admin section credentials : the [admin/.htaccess](admin/.htaccess) file references the .htpasswd file containing the admin credentials. Generate the latter using the following command : 
```bash
$ htpasswd -c /path/to/my/web/directory/passwords admin
```

###Running tests

```bash
$ ./vendor/phpunit/phpunit/phpunit
```

An HTML coverage report will be generated in the coverage/ folder.

###Using Geotime

Two main places :
* The [index.html](index.html) page at the directory root.
* The [admin section main page](admin/index.php) allowing authorized users to import geographical data into Geotime.
