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
?>

<div class="box">
<h2><i class="fa fa-plus"></i> Nová firma</h2>

<form action="firma_ins.php" method="post">

    <label>Meno firmy</label>
    <input type="text" name="meno_firmy" required>

    <label>Ičo (max 20 znakov)</label>
    <input type="text" name="ico" maxlength="20" required>

    <label>Dič (max 20 znakov)</label>
    <input type="text" name="dic">

    <label>Ulica</label>
    <input type="text" name="ulica" maxlength="20" required>

    <label>Mesto</label>
    <input type="text" name="mesto" required>

    <label>PSČ (max 10 znakov)</label>
    <input type="text" name="psc" maxlength="10" required>

    <label>Štát</label>
    <input type="text" name="stat" >

    <label>Názov banky</label>
    <input type="text" name="banka_nazov" >

    <label>IBAN (max 34 znakov) </label>
    <input type="text" name="iban" maxlength="34"    required>

    <label>SWIFT</label>
    <input type="text" name="swift" >

    <button type="submit" class="btn save">
        <i class="fa fa-save"></i> Pridať firmu
    </button>

</form>

<a href="index.php">
    <button class="btn back">⬅ Späť</button>
</a>

</div>
</body>
</html>
