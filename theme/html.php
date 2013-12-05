<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="http://radix.edoceo.com/css/radix.css" rel="stylesheet">
<link href="//gcdn.org/font-awesome/4.0.3/font-awesome.css" rel="stylesheet" type="text/css">
<link href="<?php echo radix::link('/bbd.css'); ?>" rel="stylesheet" type="text/css">
<script src="http://gcdn.org/zepto/1.0/zepto.js"></script>
<script src="http://gcdn.org/moment/2.4.0/moment.js"></script>
<script src="<?php echo radix::link('/bbd.js'); ?>"></script>
<script>
bbd.host = '<?=radix::$host;?>';
bbd.base = '<?=radix::$base;?>';
</script>
<title><?php echo $_ENV['title']; ?></title>
</head>
<body>
<header>
<div style="float:right;line-height:32px;"><a href="http://edoceo.com/creo/bbd">BigBlueDashboard</a></div>
<nav>
<ul class="h">
<li><a href="<?=radix::link('/'); ?>"><i class="fa fa-tachometer"></i> Dashboard</a></li>
<li><a href="<?=radix::link('/queue'); ?>"><i class="fa fa-refresh"></i> Queue</a></li>
<li><a href="<?=radix::link('/status'); ?>"><i class="fa fa-check"></i> Status</a></li>
<li><a href="<?=radix::link('/config'); ?>"><i class="fa fa-cogs"></i> Config</a></li>
</ul>
</nav>
</header>

<?php
echo $this->body;
?>
</body>
</html>
