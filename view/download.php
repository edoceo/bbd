<?php

$mid = $_GET['m'];
$bbm = new BBB_Meeting($mid);

$path = BBB::RAW_ARCHIVE_PATH . "/$mid";
$file = "$path.tgz";

if (!is_file($file)) {
    # $cmd = "/bin/tar --create --directory $path . |/bin/gzip >$file 2>/tmp/err.out";
    $cmd = "tar -zcf $file --directory $path . 2>&1";
    # echo $cmd;
    shell_exec($cmd);
}

while (ob_get_level()) ob_end_clean();

header('Content-Disposition: attachment; filename="Meeting_' . $bbm->code . '.tgz"');
header('Content-Length: ' . filesize($file));
header('Content-Transfer-Encoding: binary');
header('Content-Type: application/octet-stream');

readfile($file);

exit;