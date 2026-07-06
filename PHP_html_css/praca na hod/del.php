<?php
require('konekt.php');

$id_osoba = $_GET['id_osoba'];


if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
		$sql=mysqli_query($spojenie,"DELETE FROM osoba
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