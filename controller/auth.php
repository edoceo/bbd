<?php
/**
	Auth Controller
*/

$u = strtolower(trim($_POST['user']));

if ($u != $_ENV['app']['user']) {
	echo '<p class="fail">Invalid Username or Password</p>';
	return(0);
}

$p = trim($_POST['pass']);
if ($p != $_ENV['app']['pass']) {
	echo '<p class="fail">Invalid Username or Password</p>';
	return(0);
}

$_SESSION['uid'] = $_ENV['app']['user'];