#!/usr/bin/php
<?php
/**
	Tools for saving, swapping and undoing user ownership of directories
	Resets the Permissions to our Magical BBB user
*/

$user_list = array(
	'freeswitch',
	'red5',
	'tomcat6',
);

/**
	Iterates over all these BBB related file and records their permissions
*/
function _save()
{
	$cmd = 'find /etc /opt /usr /var -user www-data -o -user tomcat6 -o -user freeswitch -o -user red5';
	$out = $ret = null;
	exec($cmd, $out, $ret);

	echo "Result: $ret\n";
	echo "Found: " . count($out) . " Items\n";

	$ret = array();
	foreach ($out as $obj) {
		$obj = trim($obj);
		$inf = stat($obj);

		$ret[$obj] = array(
			'uid' => $inf['uid'],
			'gid' => $inf['gid'],
			'mode' => $inf['mode'],
		);
	}

	file_put_contents('perm-save-file.json', json_encode($ret));

	// User Data
	$user = array();
	$user['freeswitch'] = posix_getpwnam('freeswitch');
	$user['tomcat6'] = posix_getpwnam('tomcat6');
	$user['red5'] = posix_getpwnam('red5');
	file_put_contents('perm-save-user.json', json_encode($user));


}

/**
	Modifies the directores so they are all owed by BBB user
	@todo make BBB user if not exists
*/
function _swap()
{
	$inf = posix_getpwnam('bbb');
	$uid = $inf['uid'];
	$gid = $inf['gid'];

	$cmd = 'find /etc /opt /usr /var -user www-data -o -user tomcat6 -o -user freeswitch -o -user red5 -o group tomcat6 ';
	$out = $ret = null;
	exec($cmd, $out, $ret);

	echo "Changing owner to user: bbb\n";
	foreach ($out as $obj) {
		$obj = trim($obj);
		chown($obj, 'bbb');
		// chgrp($obj, 'bbb');
	}

	echo "Updating Password Database with new UIDs\n";
	foreach (array('freeswitch', 'red5', 'tomcat6') as $user) {
		// $cmd = sprintf('usermod --gid %d --uid %d --non-unique %s', $gid, $uid, $user);
		$cmd = sprintf('usermod --uid %d --non-unique %s', $uid, $user);
		echo "exec: $cmd\n";
		shell_exec($cmd);
	}
}

/**
	Iterates over all these BBB related file undoes our Hacks
	@todo use chown -R from the CLI to hit only directories
*/
function _undo()
{
	$list = json_decode(file_get_contents('perm-save-file.json'), true);
	foreach ($list as $file=>$data) {
		chown($file, $data['uid']);
		chgrp($file, $data['gid']);
		chmod($file, $data['mode']);
	}

	// Restore User Data
	$list = json_decode(file_get_contents('perm-save-user.json'), true);
	print_r($list);
	foreach ($list as $user=>$data) {
		$cmd = sprintf('usermod --gid %d --uid %d %s', $data['gid'], $data['uid'], $user);
		echo "exec: $cmd\n";
		shell_exec($cmd);
	}
}

/**
	Main
*/
switch ($argv[1]) {
case 'save':
	_save();
	break;
case 'swap':
	_swap();
	break;
case 'undo':
	_undo();
	break;
}
