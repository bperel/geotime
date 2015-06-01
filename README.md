geotime
=======
Kingdoms though time. 

The goal of this project is to produce a world map showing the countries borders' evolution through time, using Wikipedia data and maps, along with some user input.


###Requirements

* *nix
* MongoDB
* NodeJS and NPM
* Apache+PHP 5 with MongoDB, GD and curl extensions


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

* Install NPM project dependencies (i.e. bower and Karma dependencies):
```bash
$ npm install
```

* Install Bower project dependencies :
```bash
$ bower install
```

`node command not found` error ? Have a look at the Troubleshooting session at the bottom of this page.

* Set up some rights : 
```bash
$ chmod -R +w test/phpunit/cache
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

###Troubleshooting

During the installation of the NodeJS packages, some errors (`node command not found` or similar) may occur if you're using a Debian-based system. As the NodeJS documentation says :
> The upstream name for the Node.js interpreter command is "node".
> In Debian the interpreter command has been changed to "nodejs".
> This was done to prevent a namespace collision: other commands use the same name in their upstreams, such as ax25-node from the "node" package.

If running the command ```node -v``` doesn't indicate anything else than a list of related packages, yon can safely create a symbolic link to make the NPM packages use your NodeJS binary :
```bash
ln -s /usr/bin/nodejs /usr/bin/node
```
