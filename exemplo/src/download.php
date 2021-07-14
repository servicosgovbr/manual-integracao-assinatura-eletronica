<?php

header ("Content-Type: application/pkcs7-mime");
header ("Content-Disposition: attachment; filename=pcks7.p7s");
header("Content-Length: " . filesize("$myFile"));
$fp = fopen("/tmp/pkcs", "r");
fpassthru($fp);

?>