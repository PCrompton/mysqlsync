<?php

$inputun = $_POST["un"];
$inputpw = $_POST["pw"];
$inputsn = $_POST["sn"];

$dbA = 'dbA';
$dbB = 'dbB';
$buf = 'buf';

$input_cred = array($inputsn, $inputun, $inputpw);
$dbA_cred = array($inputsn, $inputun, $inputpw, $dbA);
$dbB_cred = array($inputsn, $inputun, $inputpw, $dbB);
$buf_cred = array($inputsn, $inputun, $inputpw, $buf);


?>