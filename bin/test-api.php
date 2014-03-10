#!/usr/bin/php
<?php

$HOST=getenv('HOST');
$SALT=getenv('SALT');

if (empty($HOST)) {
	$HOST = 'localhost';
}
if (empty($SALT)) {
	echo "The SALT must be provided in an environment var\n";
	exit;
}

$base = "http://$SALT@$HOST/bbd";

echo "BBB API Test on $base\n";

# List Meetings
_curl("$base/api/meeting");

# List Live Meetings
_curl("$base/api/meeting?status=live");

# Start a Meeting
_curl("$base/api/meeting", array(
	'name' => 'TestMeeting',
));

# @todo Parse Above

# Meeting Information
_curl("$base/api/meeting/$meet");

# Rebuild Meeting
_curl("$base/api/meeting/$meet");
// echo curl -v -M POST \
// 	--data 'action=rebuild' \
// 	$base/api/meeting/$meet

# Meeting Media Information
_curl("$base/api/meeting/$meet/audio");

_curl("$base/api/meeting/$meet/video");

_curl("$base/api/meeting/$meet/media");

# Delete the Meeting and All it's Stuff
_curl("$base/api/meeting/$meet", 'DELETE');

function _curl($uri,$arg)
{
	$ch = curl_init($uri);
	curl_setopt($chk, CURLOPT_VERBOSE, true);
	if (is_array($arg)) {
		// curl_setopt($ch, CURLOPT_POST, true);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		// curl_setopt($ch, CURLOPT_HEADER, array(
		//      'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
		// ));
	} elseif (is_string($arg)) {
		switch (strtoupper($arg)) {
		case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
		}
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$ret = curl_exec($ch);
	// $inf = curl_getinfo($ch);
	curl_close($ch);

	print_r($ret);
}