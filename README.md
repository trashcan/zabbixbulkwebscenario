Using this script
=================

This script allows you to take a newline separated list of domains and import them as Zabbix web scenarios.

First edit the zabbix API username, password, and URL at the top of import.php:

    define('URL', 'http://somedomain/zabbix/api_jsonrpc.php');
    define('USER', '');
    define('PASS', '');

Place the list of domains in a newline separated text file.

Then run import.php and specify the host name that the web scenarios will be attached to and the file with the domains.
    ./import "Zabbix Host Name" domain-list.txt

All of the web scenarios will check for a HTTP 200 response by default.
