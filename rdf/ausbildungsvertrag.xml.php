<?php
/* Copyright (C) 2013 FH Technikum-Wien
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
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>
 *          Karl Burkhart <burkhart@technikum-wien.at>
 */
header("Content-type: application/xhtml+xml");
require_once('../config/vilesci.config.inc.php');
require_once('../include/functions.inc.php');
require_once('../include/studiengang.class.php');
require_once('../include/student.class.php');
require_once('../include/prestudent.class.php');
require_once('../include/adresse.class.php');

$uid_arr = (isset($_REQUEST['uid'])?$_REQUEST['uid']:null);

$uid_arr = explode(";",$uid_arr);

echo "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>\n"; 
echo "<ausbildungsvertraege>\n";

$uid = isset($uid_arr[1])?$uid_arr[1]:$uid_arr[0];

$student_help = new student(); 
// an 2ter stelle da im Aufruf vom FAS ;<uid>; der erste immer '' ist
if($student_help->load($uid))
{
    $studiengang = new studiengang();
    $studiengang->load($student_help->studiengang_kz);
    switch($studiengang->typ)
    {
        case 'b':
            $studTyp = 'Bachelor'; 
            $titel_kurzbz = 'BSc'; 
            break; 
        case 'm': 
            $studTyp = 'Master'; 
            $titel_kurzbz ='MSc'; 
            break; 
        case 'd':
            $studTyp = 'Diplom'; 
            break; 
        default: 
            $studTyp =''; 
            $titel_kurzbz = ''; 
    }
    echo "\t<studiengang_typ>".$studTyp."</studiengang_typ>\n";
    echo "\t<studiengang>".$studiengang->bezeichnung."</studiengang>\n";
}

foreach($uid_arr as $uid)
{
	if($uid=='')
		continue;
		 
	echo "\t<ausbildungsvertrag>\n"; 

	$student = new student();
	if($student->load($uid))
	{
			$datum_aktuell = date('d.m.Y');
			$gebdatum = date('d.m.Y',strtotime($student->gebdatum));
			$studiengang = new studiengang();
			$studiengang->load($student->studiengang_kz);
			
			
			echo "\t\t<quote>1</quote>\n"; 
			echo "\t\t<anrede>".$student->anrede."</anrede>\n";
			echo "\t\t<vorname>".$student->vorname." ".$student->vornamen."</vorname>\n";
			echo "\t\t<vornamen>".$student->vornamen."</vornamen>\n";
			echo "\t\t<nachname>".$student->nachname."</nachname>\n";
			echo "\t\t<titelpre>".$student->titelpre."</titelpre>\n";
			echo "\t\t<titelpost>".$student->titelpost."</titelpost>\n";
			echo "\t\t<gebdatum>".$gebdatum."</gebdatum>\n";
			echo "\t\t<svnr>".$student->svnr."</svnr>\n";
			echo "\t\t<matrikelnr>".trim($student->matrikelnr)."</matrikelnr>\n";
			echo "\t\t<studiengang>".$studiengang->bezeichnung."</studiengang>\n";
			echo "\t\t<studiengang_englisch>".$studiengang->english."</studiengang_englisch>\n";
            echo "\t\t<studiengang_kurzbz>".$studiengang->kurzbzlang."</studiengang_kurzbz>\n";
			echo "\t\t<studiengang_kz>".sprintf('%04s', $studiengang->studiengang_kz)."</studiengang_kz>\n";
            echo "\t\t<studiengangSprache>".$studiengang->sprache."</studiengangSprache>"; 
            
            // check ob Quereinsteiger
            $prestudent = new prestudent(); 
            $ausbildungssemester = ($prestudent->getFirstStatus($student->prestudent_id, 'Student'))?$prestudent->ausbildungssemester:'';           
            echo "\t\t<semesterStudent>".$ausbildungssemester."</semesterStudent>";
            
            switch($studiengang->typ)
            {
                case 'b':
                    $studTyp = 'Bachelor'; 
                    $titel_kurzbz = 'BSc'; 
                    break; 
                case 'm': 
                    $studTyp = 'Master'; 
                    $titel_kurzbz ='MSc'; 
                    break; 
                case 'd':
                    $studTyp = 'Diplom'; 
                    break; 
                default: 
                    $studTyp =''; 
                    $titel_kurzbz = ''; 
            }
            
            echo "\t\t<titel_kurzbz>".$titel_kurzbz."</titel_kurzbz>\n"; 
			echo "\t\t<studiengang_typ>".$studTyp."</studiengang_typ>\n";
			echo "\t\t<studiengang_sprache>".$studiengang->sprache."</studiengang_sprache>\n";
			echo "\t\t<studiengang_maxsemester>".$studiengang->max_semester."</studiengang_maxsemester>\n";
			echo "\t\t<datum_aktuell>".$datum_aktuell."</datum_aktuell>\n";

			$adresse = new adresse();
			$adresse->load_pers($student->person_id);
			
			foreach($adresse->result as $row_adresse)
			{
				if($row_adresse->zustelladresse)
				{
					echo "\t\t<strasse>".$row_adresse->strasse."</strasse>\n";
					echo "\t\t<plz>".$row_adresse->plz." ".$row_adresse->ort."</plz>\n";
					break;
				}
			}
			$prestudent = new prestudent();
			$prestudent->getLastStatus($student->prestudent_id, null, 'Student');
			
			if($prestudent->orgform_kurzbz!='')
				$orgform = $prestudent->orgform_kurzbz;
			else
				$orgform = $studiengang->orgform_kurzbz;
			echo "\t\t<orgform>".$orgform."</orgform>\n";	
	} 
	echo "\t</ausbildungsvertrag>\n";
}
echo "</ausbildungsvertraege>"; 

?>