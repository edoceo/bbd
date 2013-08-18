<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="http://radix.edoceo.com/css/radix.css" rel="stylesheet">
<link href="//gcdn.org/font-awesome/3.2.1/font-awesome.css" rel="stylesheet" type="text/css">
<link href="<?php echo radix::link('/bbd.css'); ?>" rel="stylesheet" type="text/css">
<script src="<?php echo radix::link('/bbd.js'); ?>"></script>
<title><?php echo $_ENV['title']; ?></title>
</head>
<body>
<header>
<div style="float:right;line-height:32px;"><a href="http://edoceo.com/creo/bbd">BigBlueDashboard</a></div>
<nav>
<ul class="h">
<li><a href="<?php echo radix::link('/'); ?>"><i class="icon-dashboard"></i> Dashboard</a></li>
<li><a href="<?php echo radix::link('/status'); ?>"><i class="icon-check"></i> Status</a></li>
<li><a href="<?php echo radix::link('/config'); ?>"><i class="icon-cogs"></i> Config</a></li>
</ul>
</nav>
</header>

<?php
echo $this->body;
?>
</body>
</html>
