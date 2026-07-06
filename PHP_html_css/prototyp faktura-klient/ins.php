<?php
require('konekt.php');

$cislo_faktury     = $_POST['cislo_faktury'];
$datum_vystavenia  = $_POST['datum_vystavenia'];
$datum_splatnosti  = $_POST['datum_splatnosti'];
$celkova_suma      = $_POST['celkova_suma'];
$id_firma          = $_POST['id_firma'];

$sql = mysqli_prepare($spojenie,
"INSERT INTO xsedivyr_faktura
(cislo_faktury, datum_vystavenia, datum_splatnosti, celkova_suma, id_firma)
VALUES (?, ?, ?, ?, ?)");

mysqli_stmt_bind_param($sql, "sssdi",
    $cislo_faktury,
    $datum_vystavenia,
    $datum_splatnosti,
    $celkova_suma,
    $id_firma
);

mysqli_stmt_execute($sql);

header("Location: index.php");
exit;
?>