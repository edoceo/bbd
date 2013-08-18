<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="http://radix.edoceo.com/css/radix.css">
<link href="//gcdn.org/font-awesome/3.2.1/font-awesome.css" rel="stylesheet" type="text/css">
<!-- <script src="/js/app.js"></script> -->
<style>
tr:hover {
    background:#ccc;
} 
</style>
<title>BigBlueButton Dashboard</title>
</head>
<body>
<header>
<nav>
<ul>
<li><a href="<?php echo radix::link('/'); ?>">Dashboard</a></li>
<li><a href="<?php echo radix::link('/status'); ?>">Status</a></li>
<li><a href="<?php echo radix::link('/config'); ?>">Config</a></li>
</ul>
</nav>
</header>

<?php
echo $this->body;
?>
</body>
</html>