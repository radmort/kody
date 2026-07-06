<?php
require('konekt.php');

$meno_firmy   = $_POST['meno_firmy'];
$ico          = $_POST['ico'];
$dic          = $_POST['dic'];
$ulica        = $_POST['ulica'];
$mesto        = $_POST['mesto'];
$psc          = $_POST['psc'];
$stat         = $_POST['stat'];
$banka_nazov  = $_POST['banka_nazov'];
$iban         = $_POST['iban'];
$swift        = $_POST['swift'];

$sql = mysqli_prepare($spojenie,
"INSERT INTO xsedivyr_firma
(meno_firmy, ico, dic, ulica, mesto, psc, stat, banka_nazov, iban, swift)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

mysqli_stmt_bind_param($sql, "ssssssssss",
    $meno_firmy,
    $ico,
    $dic,
    $ulica,
    $mesto,
    $psc,
    $stat,
    $banka_nazov,
    $iban,
    $swift
);

mysqli_stmt_execute($sql);

header("Location: index.php");
exit;
?>
