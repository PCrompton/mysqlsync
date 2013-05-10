<?php

$trgun = 'root';
$trgpw = 'root';
$trgdb = 'target';
$trgsrv = 'localhost';
$dbA = 'dbA';
$dbB = 'dbB';
$buf = 'buf';

$srcun = 'adminlG1Xtn1';
$srcpw = 'GAKNHxdTBIyu';
$srcdb = 'source';
$srcsrv = '127.12.60.129';

$trgmain = array($trgsrv, $trgun, $trgpw, $trgdb);
$trgclone = array($trgsrv, $trgun, $trgpw, $srcdb);
$srcmain = array($srcsrv, $srcun, $srcpw, $srcdb);
$srcclone = array($srcsrv, $srcun, $srcpw, $trgdb);

$dbA_cred = array($trgsrv, $trgun, $trgpw, $dbA);
$dbB_cred = array($trgsrv, $trgun, $trgpw, $dbB);
$buf_cred = array($trgsrv, $trgun, $trgpw, $buf);
$cred = array($trgsrv, $trgun, $trgpw);
?>