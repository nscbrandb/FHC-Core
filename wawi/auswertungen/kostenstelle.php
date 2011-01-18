<?php
/* Copyright (C) 2010 FH Technikum Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Christian Paminger <christian.paminger@technikum-wien.at>,
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at> and
 *          Karl Burkhart <karl.burkhart@technikum-wien.at>.
 */
/**
 * Auswertung der Bestellungen und Rechnungen auf Kostenstellen
 */
require_once('../../config/wawi.config.inc.php');
require_once('../auth.php');
require_once('../../include/benutzerberechtigung.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/wawi_rechnung.class.php');
require_once('../../include/wawi_bestellung.class.php');
require_once('../../include/wawi_kostenstelle.class.php');
require_once('../../include/studiensemester.class.php');
require_once('../../include/tags.class.php');
require_once('../../include/geschaeftsjahr.class.php');
require_once('../../include/datum.class.php');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

$kst_array = $rechte->getKostenstelle();

if(count($kst_array)==0)
	die('Sie benoetigen eine Kostenstellenberechtigung um diese Seite anzuzeigen');

$datum_obj = new datum();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>WaWi - Kostenstelle - Auswertung</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<link rel="stylesheet" href="../../skin/jquery.css" type="text/css">
	<link rel="stylesheet" href="../../skin/tablesort.css" type="text/css">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/wawi.css" type="text/css">
	
			
	<script type="text/javascript" src="../../include/js/jquery.js"></script> 
	<script type="text/javascript">
 	function alleMarkieren(checked)
 	{
 	 	inputs = document.getElementsByTagName('input');

 	 	for each(i in inputs)
 	 	{
 	 	 	if(i.type=='checkbox')
 	 	 	{
 	 	 	 	i.checked=checked;
 	 	 	}
 	 	}
 	}
	</script>
</head>
<body>
<h1>Bericht - Kostenstelle</h1>
<?php

	$db = new basis_db();
	
	//Vom Studiensemester
	
	echo '
	<form action="'.$_SERVER['PHP_SELF'].'" method="POST">
	Geschäftsjahr
	<SELECT name="geschaeftsjahr" >';
	$gj = new geschaeftsjahr();
	
	$geschaeftsjahr = isset($_POST['geschaeftsjahr'])?$_POST['geschaeftsjahr']:$gj->getakt();	
	
	$gj->getAll();

	foreach ($gj->result as $gjahr)
	{
		if($gjahr->geschaeftsjahr_kurzbz==$geschaeftsjahr)
			$selected='selected';
		else 
			$selected='';
		echo '<option value="'.$gjahr->geschaeftsjahr_kurzbz.'" '.$selected.'>'.$gjahr->geschaeftsjahr_kurzbz.'</option>';				
	}
	echo '
	</SELECT>
	<input type="submit" value="Anzeigen" name="show">
	</form>';

	$gj= new geschaeftsjahr();
	$gj->load($geschaeftsjahr);

	$kstIN=$db->implode4SQL($kst_array);
	//Tabelle auf Basis der Bestellungen
	$qry = "SELECT 
				sum(menge*preisprove*(100+mwst)/100) as brutto_bestellung,
				0 as brutto_rechnung,
				tbl_bestellung.kostenstelle_id
			FROM 
				wawi.tbl_bestellung 
				JOIN wawi.tbl_bestelldetail USING(bestellung_id)
			WHERE
				tbl_bestellung.insertamum>='$gj->start' AND tbl_bestellung.insertamum<'$gj->ende' 
				AND kostenstelle_id IN($kstIN)
			GROUP BY kostenstelle_id
			UNION
			SELECT 
				0 as brutto_bestellung,
				sum(betrag*(100+mwst)/100) as brutto_rechnung,
				tbl_bestellung.kostenstelle_id
			FROM 
				wawi.tbl_bestellung 
				JOIN wawi.tbl_rechnung USING(bestellung_id)
				JOIN wawi.tbl_rechnungsbetrag USING(rechnung_id)
			WHERE
				tbl_bestellung.insertamum>='$gj->start' AND tbl_bestellung.insertamum<'$gj->ende' 
				AND kostenstelle_id IN($kstIN)
			GROUP BY kostenstelle_id
			";
	
	if($result = $db->db_query($qry))
	{
		while($row = $db->db_fetch_object($result))
		{
			if(!isset($kst[$row->kostenstelle_id]['rechnung']))
				$kst[$row->kostenstelle_id]['rechnung']=0;
			if(!isset($kst[$row->kostenstelle_id]['bestellung']))
				$kst[$row->kostenstelle_id]['bestellung']=0;
			
			$kst[$row->kostenstelle_id]['rechnung']+=$row->brutto_rechnung;
			$kst[$row->kostenstelle_id]['bestellung']+=$row->brutto_bestellung;
		}
	}
	else
		die('Fehler bei Datenbankzugriff');

	echo '<span style="font-size: small">Zeitraum: ',$datum_obj->formatDatum($gj->start,'d.m.Y'),' - ',$datum_obj->formatDatum($gj->ende,'d.m.Y').'</span>';
	echo '
	<script type="text/javascript">
	$(document).ready(function() 
		{
			$("#myTable").tablesorter(
			{
				sortList: [[1,0]],
				widgets: ["zebra"]
			});			
	 	});
	 </script>';
	echo '<table id="myTable" class="tablesorter" style="width: auto;">
			<thead>
				<tr>
					<th>ID</th>
					<th>Bezeichnung</th>
					<th>Kz</th>
					<th>Bestellungen</th>
					<th>Rechnungen</th>
					<th>Restbudget (Rechnung)</th>
				</tr>
			</thead>
			<tbody>';
	
	$gesamt_rechnung = 0;
	$gesamt_bestellung = 0;
	
	foreach($kst_array as $row)
	{
		$id = $row;
		if(isset($kst[$id]))
			$brutto = $kst[$id];
		else
		{
			$brutto['bestellung']=0;
			$brutto['rechnung']=0;
		}
		
		$kostenstelle = new wawi_kostenstelle();
		$kostenstelle->load($id);
		$budget = $kostenstelle->getBudget($id, $gj->geschaeftsjahr_kurzbz);
		$restbudget = $budget - $brutto['rechnung'];
		echo '<tr>';

		echo '<td>',$id,'</td>';
		echo '<td>',$kostenstelle->bezeichnung,'</td>';
		echo '<td>',$kostenstelle->kurzbz,'</td>';
		echo '<td class="number">',number_format($brutto['bestellung'],2,',','.'),'</td>';
		echo '<td class="number">',number_format($brutto['rechnung'],2,',','.'),'</td>';
		if($restbudget>0)
			$class='number_positive';
		elseif($restbudget<0)
			$class='number_negative';
		else
			$class='number';
		echo '<td class="',$class,'">',number_format($restbudget,2,',','.').'</td>';
		echo '</tr>';
		
		$gesamt_rechnung += $brutto['rechnung'];
		$gesamt_bestellung += $brutto['bestellung'];
	}
	echo '
		</tbody>
		<tfoot>
			<tr>
				<th></th>
				<th></th>
				<th>Summe:</th>
				<th class="number">',number_format($gesamt_bestellung,2,',','.'),'</th>
				<th class="number">',number_format($gesamt_rechnung,2,',','.'),'</th>
				<th></th>
			</tr>
		</tfoot>
		</table>';
?>
<br /><br /><br /><br /><br /><br /><br /><br />
</body>
</html>