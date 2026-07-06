<?php
require('konekt.php');
if (!$spojenie) {
    die("Chyba pripojenia: " . mysqli_connect_error());
}

if (!isset($_GET['id'])) {
    die("Chýba ID faktúry.");
}

$id = intval($_GET['id']);

// UPDATE po odoslaní formulára
if (isset($_POST['ulozit'])) {
    $cislo_faktury = $_POST['cislo_faktury'];
    $datum_vystavenia = $_POST['datum_vystavenia'];
    $datum_splatnosti = $_POST['datum_splatnosti'];
    $celkova_suma = $_POST['celkova_suma'];
    $stav = $_POST['stav'];

    $sql = "UPDATE xsedivyr_faktura
            SET cislo_faktury = ?,
                datum_vystavenia = ?,
                datum_splatnosti = ?,
                celkova_suma = ?,
                stav = ?
            WHERE id_faktura = ?";

    $stmt = mysqli_prepare($spojenie, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "sssdsi",
        $cislo_faktury,
        $datum_vystavenia,
        $datum_splatnosti,
        $celkova_suma,
        $stav,
        $id
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: index.php");
    exit;
}

// Načítanie dát faktúry
$sql = "SELECT * FROM xsedivyr_faktura WHERE id_faktura = ?";
$stmt = mysqli_prepare($spojenie, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$vysledok = mysqli_stmt_get_result($stmt);
$faktura = mysqli_fetch_assoc($vysledok);

if (!$faktura) {
    die("Faktúra neexistuje.");
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Upraviť faktúru</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }

        .box {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        h2 {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        .btn {
            margin-top: 20px;
            padding: 10px;
            border: none;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .save {
            background: #2980b9;
            color: white;
        }

        .back {
            background: #bdc3c7;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="box">
    <h2><i class="fa fa-pen"></i> Upraviť faktúru</h2>

    <form method="post" action="update.php">
    <input type="hidden" name="id_faktura" value="<?= $faktura['id_faktura'] ?>">


        <label>Dátum vystavenia</label>
        <input type="date" name="datum_vystavenia"
               value="<?= $faktura['datum_vystavenia'] ?>" required>

        <label>Dátum splatnosti</label>
        <input type="date" name="datum_splatnosti"
               value="<?= $faktura['datum_splatnosti'] ?>">

        <label>Celková suma (€)</label>
        <input type="number" step="0.01" name="celkova_suma"
               value="<?= $faktura['celkova_suma'] ?>" required>

        <label>Stav</label>
        <select name="stav">
            <option <?= $faktura['stav']=="Vystavená"?"selected":"" ?>>Vystavená</option>
            <option <?= $faktura['stav']=="Uhradená"?"selected":"" ?>>Uhradená</option>
            <option <?= $faktura['stav']=="Po splatnosti"?"selected":"" ?>>Po splatnosti</option>
        </select>

        <button type="submit" name="ulozit" class="btn save">
            💾 Uložiť zmeny
        </button>
    </form>

    <a href="index.php">
        <button class="btn back">⬅ Späť</button>
    </a>
</div>

</body>
</html>

<?php mysqli_close($spojenie); ?>
