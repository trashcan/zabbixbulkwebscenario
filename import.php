#!/usr/bin/php
<?php
define('URL', 'http://somedomain/zabbix/api_jsonrpc.php');
define('USER', '');
define('PASS', '');
$debug = 0;

require('Requests.php');
Requests::register_autoloader();
$authid = authenticate(USER, PASS);

if (!isset($argv[2])) { 
    die("Not enough arguments. Specify a file with a list of newline separated domains and a hostname to attach them to.
{$argv[0]} 'host name' filename.txt\n");
}

$hostname = $argv[1];
$hostid = hostname_to_hostid($hostname);
if (!$hostid) die("Failed to convert $hostname to a hostname. Please double check it\n");

$domains = file($argv[2]);
if (!$domains) die("Failed to open {$argv[2]} to read a list of domains.\n");
foreach ($domains as $d) {
	$d = trim($d);
    create_web_scenario($d, $hostid, $hostname);
}
exit;

function hostname_to_hostid($hostname) {
    global $authid;
    $request = Array(
            'method' => 'host.get',
            'params' => Array(
                'filter' => Array(
                    'host' => Array(
                        $hostname,
                    ),
                ),
            ),
        );
    $response = api_request($request, $authid);
    if (count($response) == 1) {
        return $response[0]->hostid;
    } else {
        die("$hostname converted to " . count($response) . " hostids. Not sure what to use.\n");
    }
}

function create_web_scenario($domain, $hostid, $hostname) {
    global $authid;
    $request = Array(
    	'method' => 'httptest.create',
        'params' => Array(
            'hostid' => $hostid,
            'name' => $domain,
            'steps' => Array(Array( 
                'name' => "$domain http 200 check",
                'url' => "http://$domain",
                'status_codes' => 200,
                'no' => '1',
                )),
            ),
        );
    $response = api_request($request, $authid);
    $httptestid = $response->httptestids[0];

    $description = "$domain http 200 check failed!";
    $expression = '{' . $hostname. ":web.test.fail[$domain].last(0)}#0";
    create_trigger($description, $expression);
    print "Web scenario for $domain created.\n";
}

function create_trigger($description, $expression) {
    global $authid; 

    $request = Array(
        'method' => 'trigger.create',
        'params' => Array(
            'description' => $description,
            'expression' => $expression,
            ),
        );

    $response = api_request($request, $authid);
}

function authenticate($user, $pass) {
    $request = Array('jsonrpc' => '2.0',
            'method' => 'user.login',
            'params' => Array(
                'user' => $user,
                'password' => $pass,
                ),
            'id' => 1,
            );
    
    $response = api_request($request);
    return $response;
}

function api_request($variables, $authid = false) {
    global $debug;
    $headers = Array('Content-Type' => 'application/json');
    if ($authid) {
        $variables['auth'] = $authid;
    }
    if (!isset($variables['id'])) {
        $variables['id'] = 1;
    }
    $variables['jsonrpc'] = '2.0';

    if ($debug) {
        print "Request:\n";
        print_r($variables);
    }

    $response = Requests::post(URL, $headers, json_encode($variables));
    $data = json_decode($response->body);

    if ($debug) {
        print "Response\n";
        print_r($data);
    }
    if (isset($data->result)) {
        return $data->result;
    }

    if (isset($response->code)) {
        print_r($response);
        die();
    } 

    if (isset($data->error)) {
        print_r($data->error);
        die();
    }

    return $data;
}

