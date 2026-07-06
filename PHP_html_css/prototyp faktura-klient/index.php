<?php
require('konekt.php');
if (!$spojenie) {
    die("Chyba pripojenia: " . mysqli_connect_error());
}

$povolene = [
    'datum' => 'f.datum_splatnosti',
    'firma' => 'fi.meno_firmy',
    'suma'  => 'f.celkova_suma'
];

// default hodnoty
$sort = $_GET['sort'] ?? 'id';
$orderBy = $povolene[$sort] ?? 'f.id_faktura';
$direction = 'ASC';

// strankovanie, max 10 faktur na stranu 

$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$countSql = "SELECT COUNT(*) AS total FROM xsedivyr_faktura";
$countRes = mysqli_query($spojenie, $countSql);
$totalRows = mysqli_fetch_assoc($countRes)['total'];
$totalPages = ceil($totalRows / $limit);
$poradie = $offset + 1;

// načítanie z URL
if (isset($_GET['sort']) && isset($povolene[$_GET['sort']])) {
    $orderBy = $povolene[$_GET['sort']];
}

if (isset($_GET['dir']) && in_array($_GET['dir'], ['ASC','DESC'])) {
    $direction = $_GET['dir'];
}

$sql = mysqli_query($spojenie, "
SELECT 
    f.id_faktura,
    f.cislo_faktury,
    f.datum_vystavenia,
    f.datum_splatnosti,
    f.celkova_suma,
    f.mena,
    f.stav,
    fi.meno_firmy AS meno_firmy
FROM xsedivyr_faktura f
JOIN xsedivyr_firma fi ON f.id_firma = fi.id_firma
ORDER BY $orderBy $direction
LIMIT $limit OFFSET $offset
");

?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Evidencia faktúr</title>

    <!-- Font pre ikony -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }

        .header-img {
            display: block;
            margin: 0 auto 30px auto; 
        }

        h1 {
            margin-bottom: 15px;
        }

        .add-btn {
            display: inline-flex;          
            align-items: center;           
            justify-content: center;       
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #2ecc71;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

       th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #34495e;
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0px;
        }

        .pagination li a {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid gray;
            color: black;
            margin: 0 4px;
            border-radius: 5px;
            transition: background-color 1s;  
        }

        .pagination li a.active {
            background-color: #34495e;
            color: white;
        }

        .pagination li a:hover:not(.active) {
            background-color: lightgray;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .actions a {
            margin: 0 5px;
            color: #333;
            font-size: 18px;
        }

        .actions a.edit { color: #2980b9; }
        .actions a.delete { color: #e74c3c; }
        .actions a.view { color: #27ae60; }

        .actions a:hover {
            opacity: 0.7;
        }
    </style>
</head>
<body>
<img src="img/fakturka logo.png" class="header-img" style="float:right;width:314px;height:200px;">
<h1>📄 Evidencia faktúr</h1>

<div style="margin-bottom:20px; display:flex; gap:10px;">
    <a href="new.php" class="add-btn">
    <img src="img/pen-and-paper.png" alt="Nová faktúra" style="width:20px;height:auto;cursor:pointer;"> Pridať faktúru
    </a>
    <a href="firma_new.php" class="add-btn">
        <img src="img/office-building.png" alt="Nová firma" style="width:20px;height:auto;cursor:pointer;"> Pridať firmu
    </a>
</div>

<div style="margin-bottom:15px;">
    <strong>Triediť podľa:</strong>
    <a href="?sort=datum&dir=ASC">📅 Dátum ↑</a> 
    <a href="?sort=datum&dir=DESC">📅 Dátum ↓</a> 

    <a href="?sort=firma&dir=ASC">🏢 Firma A–Z</a> 
    <a href="?sort=firma&dir=DESC">🏢 Firma Z–A</a> 

    <a href="?sort=suma&dir=ASC">💰 Suma ↑</a> 
    <a href="?sort=suma&dir=DESC">💰 Suma ↓</a>
</div>

<table>
    <tr>
        <th>Poradie</th>
        <th>Číslo</th>
        <th>Vystavená</th>
        <th>Splatnosť</th>
        <th>Suma</th>
        <th>Firma</th>
        <th>Stav</th>
        <th>Akcie</th>
    </tr>

    <?php while ($riadok = mysqli_fetch_assoc($sql)) { ?>
        <tr>
            <td><?= $poradie ?></td>
            <td><?= $riadok['cislo_faktury'] ?></td>
            <td><?= $riadok['datum_vystavenia'] ?></td>
            <td><?= $riadok['datum_splatnosti'] ?></td>
            <td><?= number_format($riadok['celkova_suma'], 2) . ' ' . $riadok['mena'] ?></td>
            <td><?= $riadok['meno_firmy'] ?></td>
            <td><?= $riadok['stav'] ?></td>
            <td class="actions">
                <a href="edit.php?id=<?= $riadok['id_faktura'] ?>" class="edit" title="Upraviť">
                    <i class="fa fa-pen"></i>
                </a>
                <a href="del.php?id_faktura=<?= $riadok['id_faktura'] ?>"
                   class="delete"
                   title="Vymazať"
                   onclick="return confirm('Naozaj chcete vymazať faktúru?');">
                <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php $poradie++; } ?>

</table>

 <ul class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li>
       <a href="?page=<?= $i ?>&sort=<?= $sort ?>&dir=<?= $direction ?>"
           class="<?= ($i == $page) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    </li>
<?php endfor; ?>
</ul>

</div>
</body>
</html>

<?php mysqli_close($spojenie); ?>
