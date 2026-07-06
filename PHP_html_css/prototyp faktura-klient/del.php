<?php
require('konekt.php');

$id_faktura = $_GET['id_faktura'];


if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
		$sql=mysqli_query($spojenie,"DELETE FROM xsedivyr_faktura
									WHERE id_faktura = $id_faktura");
		if(!$sql):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:

?>
<meta http-equiv="refresh" content="0; index.php">
<?php		
	endif;
endif;
?>