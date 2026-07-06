<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="utf-8">
<title>Nová faktúra</title>

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
.save { background: #2ecc71; color: white; }
.back { background: #bdc3c7; }
</style>
</head>

<body>

<?php
require('konekt.php');

$sql = mysqli_query($spojenie,
    "SELECT id_firma, meno_firmy, ico
     FROM xsedivyr_firma");
?>

<div class="box">
<h2><i class="fa fa-plus"></i> Nová faktúra</h2>

<form action="ins.php" method="post">

    <label>Číslo faktúry</label>
    <input type="text" name="cislo_faktury" required>

    <label>Dátum vystavenia</label>
    <input type="date" name="datum_vystavenia" required>

    <label>Dátum splatnosti</label>
    <input type="date" name="datum_splatnosti">

    <label>Celková suma (€)</label>
    <input type="number" step="0.01" name="celkova_suma" required>

    <label>Firma</label>
    <select name="id_firma" required>
        <?php while($r = mysqli_fetch_assoc($sql)): ?>
            <option value="<?= $r['id_firma'] ?>">
                <?= $r['meno_firmy'] ?>, ico: <?= $r['ico'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit" class="btn save">
        <i class="fa fa-save"></i> Pridať faktúru
    </button>

</form>

<a href="index.php">
    <button class="btn back">⬅ Späť</button>
</a>

</div>
</body>
</html>
