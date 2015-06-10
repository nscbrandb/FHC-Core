<?php
/* Copyright (C) 2015 fhcomplete.org
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
 * Authors: Manfred Kindl		< manfred.kindl@technikum-wien.at >
 */
/**
 * Dokumentvorlagen
 * 
 * - Anlegen und Bearbeiten von Dokumentvorlagen
 * 
 */
require_once('../../config/vilesci.config.inc.php');
require_once('../../include/functions.inc.php');
require_once('../../include/vorlage.class.php');
require_once('../../include/benutzerberechtigung.class.php');
require_once('../../include/organisationseinheit.class.php');
require_once('../../include/studiengang.class.php');

if (!$db = new basis_db())
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$user = get_uid();
$oe_kurzbz = (isset($_REQUEST['oe_kurzbz']) ? $_REQUEST['oe_kurzbz'] : null);
$vorlage_kurzbz = (isset($_REQUEST['vorlage_kurzbz']) ? $_REQUEST['vorlage_kurzbz'] : null);
$vorlagestudiengang_id = (isset($_REQUEST['vorlagestudiengang_id']) ? $_REQUEST['vorlagestudiengang_id'] : null);
$neu = (isset($_REQUEST['neu']) ? true : false);

$studiengang = new studiengang();
$studiengang->load('0');
$default_oe = $studiengang->oe_kurzbz;


$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('basis/dokumente'))
{
	die($rechte->errormsg);
}

echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//DE" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Dokumentvorlagen Verwaltung</title>
		<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<script type="text/javascript" src="../../include/js/jquery.js"></script>
		<link rel="stylesheet" href="../../skin/tablesort.css" type="text/css"/>
		<link href="../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet" type="text/css">
		<script src="../../include/js/jquery1.9.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function()
			{
				$("#t1").tablesorter(
				{
					sortList: [[1,0],[0,0],[3,1]],
					widgets: ["zebra"]
				});
			});
			function confdel(val1,val2)
			{
				return confirm("Wollen Sie die Vorlage "+val1+" Version "+val2+" wirklich loeschen?");
			}
		</script>
	</head>
	<body class="Background_main">
		<h2>Dokumentvorlagen Verwaltung</h2>';

// Speichern einer Dokumentvorlage
if(isset($_POST['speichern']))
{

	if(!$rechte->isBerechtigt('basis/dokumente', $oe_kurzbz, 'sui'))
	{
		die($rechte->errormsg);
	}

	$dokumentvorlage = new vorlage();

	if(isset($_POST['vorlagestudiengang_id']) && $_POST['vorlagestudiengang_id']!='')
	{
		//Vorlage laden
		if(!$dokumentvorlage->loadVorlageOE($_POST['vorlagestudiengang_id']))
		{
			die($dokumentvorlage->errormsg);
		}

		$dokumentvorlage->new=false;
		$dokumentvorlage->vorlagestudiengang_id = $_POST['vorlagestudiengang_id'];
	}
	else
	{
		//Neue Vorlage anlegen
		$dokumentvorlage->new=true;
	}
	
	$studiengang = new studiengang();
	$studiengang->getStudiengangFromOe($_POST['oe_kurzbz']);
	
	if ($studiengang->studiengang_kz=='')
		$studiengang_kz = 0;
	else 
		$studiengang_kz = $studiengang->studiengang_kz;
	
	$dokumentvorlage->vorlage_kurzbz = $_POST['vorlage_kurzbz'];
	$dokumentvorlage->studiengang_kz = $studiengang_kz;
	$dokumentvorlage->version = $_POST['version'];
	$dokumentvorlage->text = $_POST['content'];
	$dokumentvorlage->oe_kurzbz = $_POST['oe_kurzbz'];
	$dokumentvorlage->style = $_POST['style'];
	$dokumentvorlage->berechtigung = $_POST['berechtigung'];
	$dokumentvorlage->anmerkung_vorlagestudiengang = $_POST['anmerkung'];

	if($dokumentvorlage->saveVorlageOE())
	{
		echo '<b>Daten wurden erfolgreich gespeichert</b>';
		//$reihungstest_id = $reihungstest->reihungstest_id;
		//$stg_kz = $reihungstest->studiengang_kz;
	}
	else
	{
		echo '<span class="input_error">'.$db->convert_html_chars($dokumentvorlage->errormsg).'</span>';
	}
	$neu=false;
}

// Speichern einer Dokumentvorlage
if(isset($_GET['delete']))
{

	if(!$rechte->isBerechtigt('basis/dokumente', $oe_kurzbz, 'suid'))
	{
		die($rechte->errormsg);
	}

	if(isset($_GET['vorlagestudiengang_id']) && $_GET['vorlagestudiengang_id']!='')
	{
		$dokumentvorlage = new vorlage();
		if($dokumentvorlage->deleteVorlagestudiengang($_GET['vorlagestudiengang_id']))
			echo '<b>Vorlage wurde erfolgreich gelöscht</b>';
		else
			echo '<span class="input_error">'.$dokumentvorlage->errormsg.'</span>';
	}
	else
		echo '<span class="input_error">Vorlagestudiengang_ID ist nicht gesetzt</span>';

	$neu=true;
}


echo '<br><table width="100%"><tr><td>';

//Vorlagen DropDown
$vorlage = new vorlage();
$vorlage->getAllVorlagen('bezeichnung');

echo "<SELECT name='vorlage_kurzbz' id='vorlage' onchange='window.location.href=this.value'>";
if($vorlage_kurzbz=='')
	$selected='selected';
else
	$selected='';
echo "<OPTION value='".$_SERVER['PHP_SELF']."?vorlage_kurzbz=&oe_kurzbz=$oe_kurzbz' $selected>Alle Vorlagen</OPTION>";
foreach ($vorlage->result as $row) 
{
	//if($reihungstest_id=='')
	//	$reihungstest_id=$row->reihungstest_id;
	if($row->vorlage_kurzbz==$vorlage_kurzbz)
		$selected='selected';
	else
		$selected='';

	echo '<OPTION value="'.$_SERVER['PHP_SELF'].'?vorlage_kurzbz='.$row->vorlage_kurzbz.'" '.$selected.'>'.$db->convert_html_chars(($row->bezeichnung==''?$row->vorlage_kurzbz:$row->bezeichnung)).'</OPTION>';
	echo "\n";
}
echo '</SELECT>';

//OE-Dropdown
$organisationseinheit = new organisationseinheit();
$organisationseinheit->getAll(true, true);

echo "<SELECT name='oe_kurzbz' id='organisationseinheit' onchange='window.location.href=this.value'>";
if($oe_kurzbz==$default_oe)
	$selected='selected';
else
	$selected='';

echo "<OPTION value='".$_SERVER['PHP_SELF']."?vorlage_kurzbz=$vorlage_kurzbz&oe_kurzbz=' $selected>Alle Organisationseinheiten</OPTION>";
foreach ($organisationseinheit->result as $row)
{
	if($row->oe_kurzbz==$oe_kurzbz)
		$selected='selected';
	else
		$selected='';

	echo "<OPTION value='".$_SERVER['PHP_SELF']."?vorlage_kurzbz=$vorlage_kurzbz&oe_kurzbz=$row->oe_kurzbz' $selected>".$db->convert_html_chars($row->organisationseinheittyp_kurzbz." ".$row->bezeichnung)."</OPTION>"."\n";
}
echo "</SELECT>";
echo "<INPUT type='button' value='Anzeigen' onclick='window.location.href=document.getElementById(\"organisationseinheit\").value;'>";
echo "</td></tr></table><br>";

if($vorlagestudiengang_id=='')
	$neu=true;
$vorlageOE = new vorlage();

if(!$neu)
{
	if(!$vorlageOE->loadVorlageOE($vorlagestudiengang_id))
		die('Vorlage existiert nicht');
}
else 
{
	$vorlageOE->vorlage_kurzbz = $vorlage_kurzbz;
	$vorlageOE->oe_kurzbz = $oe_kurzbz;
	$vorlageOE->version = ($vorlageOE->getMaxVersion($oe_kurzbz, $vorlage_kurzbz))+1;
}

//Formular zum Bearbeiten der Vorlage
echo '
<input type="button" value="Neue OE-Vorlage anlegen" onclick="window.location.href=\''.$_SERVER['PHP_SELF'].'?vorlage_kurzbz='.$vorlage_kurzbz.'&oe_kurzbz='.$oe_kurzbz.'&neu=true\'" >
<hr>
<form method="POST" action="'.$_SERVER['PHP_SELF'].'">
	<table>
		<tr>
			<td>Vorlage</td>
			<td>';
			
			if($vorlageOE->oe_kurzbz!='')
				$oe=$vorlageOE->oe_kurzbz;
			elseif($oe_kurzbz!='')
			$oe=$oe_kurzbz;
			else
				$oe=$default_oe;
			//Vorlagen DropDown
			$vorlage = new vorlage();
			$vorlage->getAllVorlagen('bezeichnung');
			
			echo '<SELECT name="vorlage_kurzbz" id="vorlage">';
			foreach ($vorlage->result as $row) 
			{
				//if($reihungstest_id=='')
				//	$reihungstest_id=$row->reihungstest_id;
				if($row->vorlage_kurzbz==$vorlageOE->vorlage_kurzbz)
					$selected='selected';
				else
					$selected='';
			
				echo '<OPTION value="'.$row->vorlage_kurzbz.'" '.$selected.'>'.$db->convert_html_chars(($row->bezeichnung==''?$row->vorlage_kurzbz:$row->bezeichnung)).'</OPTION>';
				echo "\n";
			}
			echo '</SELECT>
			
			</td>		
		<tr>
			<td>Organisationseinheit</td>
			<td>';
			//OE-Dropdown
			$organisationseinheit = new organisationseinheit();
			$organisationseinheit->getAll(true, true);
			
			echo "<SELECT name='oe_kurzbz'>";
			
			foreach ($organisationseinheit->result as $row)
			{
				//Wenn keine OE uebergeben wurde, nimm die OE vom Studiengang 0
				if($row->oe_kurzbz==$oe)
					$selected='selected';
				else
					$selected='';
			
				echo '<OPTION value="'.$row->oe_kurzbz.'" '.$selected.'>'.$db->convert_html_chars($row->organisationseinheittyp_kurzbz.' '.$row->bezeichnung).'</OPTION>';
				echo "\n";
			}
			echo '</SELECT>
			</td>
		</tr>
		<tr>
			<td>Version</td>
			<td><input type="text" size="4" maxlength="3" name="version" id="version" value="'.$db->convert_html_chars($vorlageOE->version).'"></td>
		</tr>
		<tr>
			<td>Content (XML)</td>
			<td><textarea cols="80" rows="8" name="content">'.$db->convert_html_chars($vorlageOE->text).'</textarea></td>
		</tr>
		<tr>
			<td>Style (XML)</td>
			<td><textarea cols="80" rows="8" name="style">'.$db->convert_html_chars($vorlageOE->style).'</textarea></td>
		</tr>
		<tr>
			<td>Berechtigung</td>
			<td><input type="text" size="64" maxlength="64" name="berechtigung" value="'.$db->convert_html_chars($vorlageOE->berechtigung).'"></td>
		</tr>
		<tr>
			<td>Anmerkung</td>
			<td><textarea cols="80" rows="2" name="anmerkung">'.$db->convert_html_chars($vorlageOE->anmerkung_vorlagestudiengang).'</textarea></td>
		</tr>';
		if(!$neu)
			$val = 'Änderung Speichern';
		else
			$val = 'Neu anlegen';
		
		echo '<tr>
			<td></td>
			<td>
				<input type="hidden" value="'.$vorlageOE->vorlagestudiengang_id.'" name="vorlagestudiengang_id" />
				<input type="hidden" value="'.$vorlageOE->vorlage_kurzbz.'" name="vorlage_kurzbz" />
				<input type="submit" name="speichern" value="'.$val.'">
			</td>
		</tr>
	</table>
</form>

<hr>';

if($vorlage_kurzbz!='' || $oe_kurzbz!='')
{

	$vorlage_version = new vorlage();
	$vorlage_version->getAllVersions($vorlage_kurzbz, $oe_kurzbz);
	$oe = new organisationseinheit();
	$vorlage = new vorlage();
	//echo '<span style="font-size: 9pt">Anzahl: '.$db->db_num_rows($vorlage_version->result).'</span>';
	
	echo '<table class="tablesorter" id="t1">
				<thead>
				<tr class="liste">
					<th>Vorlage</th>
					<th>Organisationseinheit</th>
					<th>Studiengang</th>
					<th>Version</th>
					<th>Anmerkung</th>
					<th colspan="2"></th>
				</tr>
				</thead>
				<tbody>';
	
	foreach($vorlage_version->result as $row)
	{
		$oe->load($row->oe_kurzbz);
		$vorlage->loadVorlage($row->vorlage_kurzbz);
		$vorlage_bezeichnung = ($vorlage->bezeichnung==''?$vorlage->vorlage_kurzbz:$vorlage->bezeichnung);
		echo '
			<tr>
				<td>'.$db->convert_html_chars($vorlage_bezeichnung).'</td>
				<td>'.$db->convert_html_chars($oe->organisationseinheittyp_kurzbz.' '.$oe->bezeichnung).'</td>
				<td>'.$db->convert_html_chars($row->studiengang_kz).'</td>
				<td>'.$db->convert_html_chars($row->version).'</td>
				<td>'.$db->convert_html_chars($row->anmerkung_vorlagestudiengang).'</td>
				<td><a href="'.$_SERVER['PHP_SELF'].'?vorlagestudiengang_id='.$row->vorlagestudiengang_id.'&vorlage_kurzbz='.$vorlage_kurzbz.'&oe_kurzbz='.$row->oe_kurzbz.'">Edit</a></td>
				<td><a href="'.$_SERVER['PHP_SELF'].'?vorlagestudiengang_id='.$row->vorlagestudiengang_id.'&vorlage_kurzbz='.$vorlage_kurzbz.'&oe_kurzbz='.$row->oe_kurzbz.'&delete" onclick="return confdel(\''.$vorlage_bezeichnung.'\',\''.$row->version.'\')">Delete</a></td>
				
			</tr>';
	}
	echo '</tbody></table>';
}

echo '	</body>
</html>';
