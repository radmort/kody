<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Nova polozka</title>
</head>

<body>
<?php
require('konekt.php');

if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
		$sql=mysqli_query($spojenie,"SELECT id_adresa, ulica, cislo_domu, mesto
									FROM adresa");
		if(!$sql):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:
?>
<form action="ins.php" method="get" name="form1" id="form1">
<table width="50%" border="1">
  <tr>
    <th scope="col">Atribúty</th>
    <th scope="col">Hodnoty</th>
  </tr>
  <tr>
    <td>Meno</td>
    <td><input name="meno" type="text" id="meno" size="20" maxlength="20" /></td>
  </tr>
  <tr>
    <td>Priezvisko</td>
    <td><input name="priezvisko" type="text" id="priezvisko" size="30" maxlength="30" /></td>
  </tr>
  <tr>
    <td>Dátum narodenia </td>
    <td><input name="datum_nar" type="date" id="datum_nar"/></td>
  </tr>
  <tr>
    <td>Rodné číslo </td>
    <td><input name="rc" type="text" id="rc" size="11" maxlength="11" /></td>
  </tr>
  <tr>
    <td>Telefónne číslo </td>
    <td><input name="cislo_tel" type="text" id="cislo_tel" size="20" maxlength="20" /></td>
  </tr>
  <tr>
    <td>Pohlavie</td>
    <td><input name="pohlavie" type="radio" value="M" checked="checked" />
      Muž
      <input name="pohlavie" type="radio" value="Z" />Žena</td>
  </tr>
  <tr>
    <td>Vek</td>
    <td><input name="vek" type="number" id="vek" size="3" maxlength="3" /></td>
  </tr>
  <tr>
    <td>Adresa</td>
    <td><select name="id_adresa" id="id_adresa">
	<?php
			while($zaznam=mysqli_fetch_row($sql)):
?>
      <option value="<?php echo $zaznam[0];?>"><?php echo $zaznam[1]." ".$zaznam[2].", ".$zaznam[3];?></option>
	  <?php		
		endwhile;
?>
    </select>
    </td>
  </tr>
</table>

<p>
  <input name="odosli" type="submit" id="odosli" value="Pridat" />
</p>
<?php		
	endif;
endif;
?>
</form>
</body>
</html>
