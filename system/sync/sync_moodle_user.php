<?php
/* Copyright (C) 2008 Technikum-Wien
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
 *          Rudolf Hangl <rudolf.hangl@technikum-wien.at>.
 */
/*
 * Synchronisiert die Lektoren und Studenten der aktuellen MoodleKurse
 * wenn kein aktuelles Studiensemester vorhanden ist, wird NICHT Synchronisiert
 */
require_once('../../config/vilesci.config.inc.php');
require_once('../../include/moodle19_course.class.php');
require_once('../../include/moodle19_user.class.php');
require_once('../../include/studiensemester.class.php');
require_once('../../include/studiengang.class.php');
require_once('../../include/mail.class.php');
	
$db = new basis_db();
$sync_lektoren_gesamt=0;
$sync_studenten_gesamt=0;
$group_updates=0;
$fehler=0;
$message='';
$message_lkt='';
$lektoren=array();

//nur Synchronisieren wenn ein aktuelles Studiensemester existiert damit keine 
//Probleme durch die Vorrueckung entstehen
$stsem = new studiensemester();
if($stsem_kurzbz=$stsem->getakt())
{
	//nur die Eintraege des aktuellen Studiensemesters syncen
	$qry = "SELECT distinct mdl_course_id FROM lehre.tbl_moodle 
			WHERE studiensemester_kurzbz=".$db->db_add_param($stsem_kurzbz)."
				AND moodle_version='1.9';";
	if($result = $db->db_query($qry))
	{
		while($row = $db->db_fetch_object($result))
		{
			$course = new moodle19_course();
			if($course->load($row->mdl_course_id))
			{
				$message_lkt='';
				//Lektoren
				$mdluser = new moodle19_user();
				$mitarbeiter = $mdluser->getMitarbeiter($row->mdl_course_id);
				
				if($mdluser->sync_lektoren($row->mdl_course_id))
				{
					$sync_lektoren_gesamt+=$mdluser->sync_create;
					$group_updates+=$mdluser->group_update;
					if($mdluser->sync_create>0 || $mdluser->group_update>0)
					{
						$message.="\nKurs: $course->mdl_fullname ($course->mdl_shortname):\n".$mdluser->log."\n";
						$message_lkt.="\nKurs: $course->mdl_fullname ($course->mdl_shortname):\n".$mdluser->log_public."\n";
					}
				}
				else 
				{
					$message.="\nFehler: $mdluser->errormsg";
					$fehler++;
				}
				
				//Studenten
				$mdluser = new moodle19_user();
				if($mdluser->sync_studenten($row->mdl_course_id))
				{
					$sync_studenten_gesamt+=$mdluser->sync_create;
					$group_updates+=$mdluser->group_update;
					if($mdluser->sync_create>0 || $mdluser->group_update>0)
					{
						$message.="\nKurs: $course->mdl_fullname ($course->mdl_shortname):\n".$mdluser->log."\n";
						$message_lkt.="\nKurs: $course->mdl_fullname ($course->mdl_shortname):\n".$mdluser->log_public."\n";
					}
				}
				else
				{
					$message.="\nFehler: $mdluser->errormsg";
					$fehler++;
				}
				
				foreach ($mitarbeiter as $uid)
				{
					if(!isset($lektoren[$uid]))
						$lektoren[$uid]='';
					$lektoren[$uid].=$message_lkt;
				}
			}
			else 
			{
				$message.="\nFehler: in der Tabelle lehre.tbl_moodle wird auf den Kurs $row->mdl_course_id verwiesen, dieser existiert jedoch nicht im Moodle!";
				$fehler++;
			}
		}
		
		if($sync_lektoren_gesamt>0 || $sync_studenten_gesamt>0 || $fehler>0 || $group_updates>0)
		{
			//Mail an die Lektoren
			foreach ($lektoren as $uid=>$message_lkt)
			{
				if($message_lkt!='')
				{
					$header = "Dies ist eine automatische Mail!\n";
					$header.= "Es wurden folgende Aktualisierungen an Ihren Moodle-Kursen durchgeführt:\n\n";
	
					$to = "$uid@".DOMAIN;
					//$to = 'oesi@technikum-wien.at';
					
					$mail = new mail($to, 'vilesci@'.DOMAIN,'Moodle - Aktualisierungen',$header.$message_lkt);
					if($mail->send())
						echo "Mail wurde an $to versandt<br>";
					else 
						echo "Fehler beim Senden des Mails an $to<br>";
				}
			}
			//Mail an Admin
			$header = "Dies ist eine automatische Mail!\n";
			$header.= "Folgende Syncros mit den MoodleKursen wurde durchgeführt:\n\n";
			$header.= "Anzahl der aktualisierten Lektoren: $sync_lektoren_gesamt\n";
			$header.= "Anzahl der aktualisierten Studenten: $sync_studenten_gesamt\n";
			$header.= "Anzahl der Fehler: $fehler\n";
			
			$to = MAIL_ADMIN;
			//$to = 'oesi@technikum-wien.at';
			
			$mail = new mail($to, 'vilesci@'.DOMAIN,'Moodle Syncro',$header.$message);
			if($mail->send())
				echo "Mail wurde an $to versandt:<br>".nl2br($header.$message);
			else 
				echo "Fehler beim Senden des Mails an $to:<br>".nl2br($header.$message);
		}
		else 
		{
			echo 'Alle Zuteilungen sind auf dem neuesten Stand';
		}
	}
	else 
	{
		echo 'Fehler bei Select:'.$qry;
	}
}
else 
	echo "Kein aktuelles Studiensemester vorhanden->kein Syncro";
?>
