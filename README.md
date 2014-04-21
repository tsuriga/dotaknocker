dotaknocker
===========

Dota API querying with Knockout

Queries its own API that queries the official DotA 2 Web API and other relevant
Steam APIs to fetch information on hero usage statistics and player aliases.

Uses player alias to look for matches and saves the results in an SQLite
database.

Uses a quickly fixed fork of DotA 2 Web API PHP wrapper by kronusme at
https://github.com/kronusme/dota2-api. You'll need to configure the path to the
file containing your own Steam API key in lib/vendor/dota2-api/config.php.

School project for JAMK University of Applied Sciences.

##Built on

* Knockout 3.1.0
* jQuery 2.1.0
* ZURB Foundation 5.2.2
* dota2-api:4b55604116 (modified config.php)
* Steam API
* SQLite 3.6.22
