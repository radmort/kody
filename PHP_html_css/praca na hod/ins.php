<?php
require('konekt.php');

$meno =$_GET['meno'];
$priezvisko =$_GET['priezvisko'];
$datum_nar =$_GET['datum_nar'];
$rc =$_GET['rc'];
$cislo_tel =$_GET['cislo_tel'];
$pohlavie =$_GET['pohlavie'];
$vek = $_GET['vek'];
$id_adresa = $_GET['id_adresa'];


if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
		$sql=mysqli_query($spojenie,"INSERT INTO osoba VALUES (null,'$meno','$priezvisko','$datum_nar','$rc','$cislo_tel','$pohlavie', $vek,$id_adresa) ");
		if(!$sql):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:

?>
<meta http-equiv="refresh" content="0; index.php">
<?php		
	endif;
endif;
?>