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
$id_osoba = $_GET['id_osoba'];


if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
		$sql=mysqli_query($spojenie,"UPDATE osoba SET meno='$meno', priezvisko= '$priezvisko', datum_nar='$datum_nar', rc='$rc', cislo_tel = '$cislo_tel', pohlavie = '$pohlavie', vek=$vek, id_adresa=$id_adresa 
									WHERE id_osoba = $id_osoba");
		if(!$sql):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:

?>
<meta http-equiv="refresh" content="0; index.php">
<?php		
	endif;
endif;
?>