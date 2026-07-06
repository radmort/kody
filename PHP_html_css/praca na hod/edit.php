<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Nova polozka</title>
</head>

<body>
<?php
require('konekt.php');

$id_osoba = $_GET['id_osoba'];
if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
		$sql_osoba=mysqli_query($spojenie,"SELECT meno,priezvisko,datum_nar,rc,cislo_tel,pohlavie,vek,id_adresa
									FROM osoba WHERE id_osoba=$id_osoba");
	
	
	
	
		if(!$sql_osoba):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:
			$row = mysqli_fetch_array($sql_osoba, MYSQLI_ASSOC);
			$meno = $row['meno'];
			$priezvisko = $row['priezvisko'];
			$datum_nar = $row['datum_nar'];
			$rc = $row['rc'];
			$cislo_tel = $row['cislo_tel'];
			$pohlavie = $row['pohlavie'];
			$vek = $row['vek'];
			$id_adresa = $row['id_adresa'];
		endif;	
			
		$sql=mysqli_query($spojenie,"SELECT id_adresa, ulica, cislo_domu, mesto
									FROM adresa");
		if(!$sql):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:
?>
<form action="update.php" method="get" name="form1" id="form1">
<table width="50%" border="1">
  <tr>
    <th scope="col">Atribúty</th>
    <th scope="col">Hodnoty</th>
  </tr>
  <tr>
    <td>Meno</td>
    <td><input name="meno" type="text" id="meno" size="20" maxlength="20" value="<?php echo $meno; ?>" required /></td>
  </tr>
  <tr>
    <td>Priezvisko</td>
    <td><input name="priezvisko" type="text" id="priezvisko" size="30" maxlength="30" value="<?php echo $priezvisko; ?>" required /></td>
  </tr>
  <tr>
    <td>Dátum narodenia </td>
    <td><input name="datum_nar" type="date" id="datum_nar" value="<?php echo $datum_nar; ?>" required /></td>
  </tr>
  <tr>
    <td>Rodné číslo </td>
    <td><input name="rc" type="text" id="rc" size="11" maxlength="11" value="<?php echo $rc; ?>" required /></td>
  </tr>
  <tr>
    <td>Telefónne číslo </td>
    <td><input name="cislo_tel" type="text" id="cislo_tel" size="20" maxlength="20"  value="<?php echo $cislo_tel; ?>" required /></td>
  </tr>
  <tr>
    <td>Pohlavie</td>
	<?php
	if($pohlavie == 'M'):	
	?>
    <td><input name="pohlavie" type="radio" value="M" checked="checked" />
      Muž
      <input name="pohlavie" type="radio" value="Z" />Žena</td>
	<?php
	else:
	?>
	<td><input name="pohlavie" type="radio" value="M" />
      Muž
      <input name="pohlavie" type="radio" value="Z" checked="checked" />Žena</td>
	 <?php 
	 endif;
	 ?>
  </tr>
  <tr>
    <td>Vek</td>
    <td><input name="vek" type="number" id="vek" size="3" maxlength="3" value="<?php echo $vek; ?>" required /></td>
  </tr>
  <tr>
    <td>Adresa</td>
    <td><select name="id_adresa" id="id_adresa">
	<?php
			while($zaznam=mysqli_fetch_row($sql)):
?>
      <option value="<?php echo $zaznam[0];?>" <?php if($zaznam[0]==$id_adresa): echo ' selected' ; endif; ?>><?php echo $zaznam[1]." ".$zaznam[2].", ".$zaznam[3];?></option>
	  <?php		
		endwhile;
?>
    </select>
    </td>
  </tr>
</table>

<p>
  <input name="id_osoba" type="hidden" value="<?php echo $id_osoba; ?>" />
  <input name="odosli" type="submit" id="odosli" value="Pridat" />
</p>
<?php		
	endif;
endif;
?>
</form>
</body>
</html>
