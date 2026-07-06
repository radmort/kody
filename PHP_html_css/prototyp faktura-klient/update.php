<?php
require('konekt.php');

if (!$spojenie) {
    die("Spojenie s databázou neprebehlo");
}

if (!isset($_POST['id_faktura'])) {
    die("Chýba ID faktúry.");
}

$id_faktura = intval($_POST['id_faktura']);

$cislo_faktury    = $_POST['cislo_faktury'] ?? null;
$datum_vystavenia = $_POST['datum_vystavenia'];
$datum_splatnosti = $_POST['datum_splatnosti'];
$celkova_suma     = $_POST['celkova_suma'];
$stav             = $_POST['stav'];

$sql = "UPDATE xsedivyr_faktura
        SET datum_vystavenia = ?,
            datum_splatnosti = ?,
            celkova_suma = ?,
            stav = ?
        WHERE id_faktura = ?";

$stmt = mysqli_prepare($spojenie, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "ssdsi",
    $datum_vystavenia,
    $datum_splatnosti,
    $celkova_suma,
    $stav,
    $id_faktura
);

mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($spojenie);

header("Location: index.php");
exit;