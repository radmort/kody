<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>RS_1phpstranka</title>
<script language="javascript">

function confirmBox(data)
{
	if(window.confirm("Ste si isty?"))
	{
	 self.location.href="del.php?id_osoba="+data;	
	}
}

</script>

<style type="text/css">
th.titulny{
		background-color: #cc66cc;
		font-size: 20px;
		padding: 10px 40px 25px 5px;
		color: #ffffff;
}

td.titulny{
		background-color: #FF91F1;
		font-size: 20px;
		padding: 10px 40px 25px 5px;
		color: #ffffff;
}

a{	
	color: #000000;
	text-decoration: none;
}
</style>
</head>

<body>
<?php
require('konekt.php');

//zoradenie podla priezviska----------
/*if(!isset($_GET['zorad'])):
	$sort = 1;
else:
	$sort = $_GET['zorad'];
endif;*/

//filter-------------
if(!isset($_GET['id_adresa'])):
	$id_adresa = 0;
else:
	$id_adresa = $_GET['id_adresa'];
endif;

?>

<form >
	<select name="id_adresa" onChange="forms[0].submit()">
		<option value="0" <?php if($id_adresa==0):echo ' selected';endif;?>>Všetky</option>
		<option value="1" <?php if($id_adresa==1):echo ' selected';endif;?>>Bottova</option>
		<option value="2" <?php if($id_adresa==2):echo ' selected';endif;?>>Vazova</option>
		<option value="3" <?php if($id_adresa==3):echo ' selected';endif;?>>Hospodarska</option>
	</select>
</form>

<?php
if(!$spojenie):
		echo "Spojenie s databazou neprebehlo";
	else:
	
	//Zoradenie podla priezviska----------
	
	/*if($sort==1):
		$sql=mysqli_query($spojenie,"SELECT osoba.id_osoba, osoba.meno, osoba.priezvisko,
									osoba.datum_nar, osoba.rc, osoba.cislo_tel, osoba.pohlavie,
									osoba.vek, adresa.ulica, adresa.cislo_domu, adresa.mesto
									FROM osoba,adresa
									WHERE osoba.id_adresa = adresa.id_adresa
									ORDER BY osoba.priezvisko DESC");
									
	else:
		$sql=mysqli_query($spojenie,"SELECT osoba.id_osoba, osoba.meno, osoba.priezvisko,
									osoba.datum_nar, osoba.rc, osoba.cislo_tel, osoba.pohlavie,
									osoba.vek, adresa.ulica, adresa.cislo_domu, adresa.mesto
									FROM osoba,adresa
									WHERE osoba.id_adresa = adresa.id_adresa
									ORDER BY osoba.priezvisko ASC");
	endif;
	*/
	
	//filter-------------
	if($id_adresa==0):
		$sql=mysqli_query($spojenie,"SELECT osoba.id_osoba, osoba.meno, osoba.priezvisko,
									osoba.datum_nar, osoba.rc, osoba.cislo_tel, osoba.pohlavie,
									osoba.vek, adresa.ulica, adresa.cislo_domu, adresa.mesto
									FROM osoba,adresa
									WHERE osoba.id_adresa = adresa.id_adresa
									ORDER BY osoba.priezvisko ASC");
									
	else:
		$sql=mysqli_query($spojenie,"SELECT osoba.id_osoba, osoba.meno, osoba.priezvisko,
									osoba.datum_nar, osoba.rc, osoba.cislo_tel, osoba.pohlavie,
									osoba.vek, adresa.ulica, adresa.cislo_domu, adresa.mesto
									FROM osoba,adresa
									WHERE osoba.id_adresa =$id_adresa
									ORDER BY osoba.priezvisko ASC");
	endif;
	
		if(!$sql):
			echo "Nepodarilo sa vytvorit SQL dotaz!";
		else:
			$x=0;
?>
<table width="100%" border="1">
  <tr>
    <th scope="col" class = "titulny">PČ</th>
    <th scope="col" class = "titulny">Meno a priezvisko <a href="index.php?zorad=1">↓</a><a href="index.php?zorad=2">↑</a> </th>
    <th scope="col" class = "titulny">datum_nar</th>
    <th scope="col" class = "titulny">rc</th>
    <th scope="col" class = "titulny">cislo_tel</th>
    <th scope="col" class = "titulny">pohlavie</th>
    <th scope="col" class = "titulny">vek</th>
    <th scope="col" class = "titulny">adresa</th>
    <th scope="col" class = "titulny"> upraviť</th>
    <th scope="col" class = "titulny">zmazať;</th>
  </tr>
<?php
			while($zaznam=mysqli_fetch_row($sql)):
			$x=$x+1;
?>
  <tr>
    <td class="titulny"><?php echo $x;?></td>
    <td><?php echo $zaznam[1]." ".$zaznam[2];?></td>
    <td><?php echo $zaznam[3];?></td>
    <td><?php echo $zaznam[4];?></td>
    <td><?php echo $zaznam[5];?></td>
    <td><?php echo $zaznam[6];?></td>
    <td><?php echo $zaznam[7];?></td>
    <td><?php echo $zaznam[8]." ".$zaznam[9].", ".$zaznam[10];?></td>
    <td><a href="edit.php?id_osoba=<?php echo $zaznam[0];?>">edit</a></td>
    <td><a href="javascript:confirmBox(<?php echo $zaznam[0];?>)">del</a></td>
  </tr>
<?php		
		endwhile;
	endif;
endif;
?>
</table>

<p><a href="new.php">pridaj</a>
  
</p>
</body>
</html>
