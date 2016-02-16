<?php
/* Copyright (C) 2006 Technikum-Wien
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
/**
 * LV Details fuer CIS Seite
 * diese Datei wird von /cis/private/lehre/lesson.php inkludiert
 */

echo '
<table class="tabcontent" id="lvmenue">
	<tr>';

$eintraegeprozeile=0;

function checkZeilenUmbruch()
{
	global $eintraegeprozeile;

	if($eintraegeprozeile>=3)
	{
		echo '</tr><tr>';
		$eintraegeprozeile=0;
	}
}
	if (!isset($DOC_ROOT) || empty($DOC_ROOT))
		$DOC_ROOT='../../..';

	$dir_name=$DOC_ROOT.'/documents';
	if(!is_dir($dir_name))
	{
		exec('mkdir -m 755 '.escapeshellarg($dir_name));
		exec('sudo chown www-data:teacher '.escapeshellarg($dir_name));
	}
	$angemeldet = true;
	if(defined('CIS_LEHRVERANSTALTUNG_WENNANGEMELDET_DETAILS_ANZEIGEN') && CIS_LEHRVERANSTALTUNG_WENNANGEMELDET_DETAILS_ANZEIGEN && !$is_lector)
	{
	    $angemeldet = false;

		$lehrveranstaltung_obj = new lehrveranstaltung();
		$result = $lehrveranstaltung_obj->getLehreinheitenOfLv($lvid, $user, $angezeigtes_stsem);

		if(count($result)>0)
		    $angemeldet = true;
	}

// ******************* MENUE NEU *************************************+
	$menu = array();

	// LVINFO
	if(!defined('CIS_LEHRVERANSTALTUNG_LVINFO_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_LVINFO_ANZEIGEN)
	{
		$text='';

		$qry = "SELECT * FROM campus.tbl_lvinfo WHERE lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER)." AND genehmigt=true AND sprache='".ATTR_SPRACHE_DE."' AND aktiv=true";
		$need_br=false;

		if($result=$db->db_query($qry))
		{
			if($db->db_num_rows($result)>0)
			{
				 $text.= "<a href=\"#\" class='Item' onClick=\"javascript:window.open('ects/preview.php?lv=$lvid&language=de','Lehrveranstaltungsinformation','width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes');\">".$p->t('global/deutsch')."&nbsp;</a>";
				 $need_br=true;
			}
		}
		$qry = "SELECT * FROM campus.tbl_lvinfo WHERE lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER)." AND genehmigt=true AND sprache='".ATTR_SPRACHE_EN."' AND aktiv=true";
		if($result=$db->db_query($qry))
		{
			if($db->db_num_rows($result)>0)
			{
				$row1=$db->db_fetch_object($result);
				$text.= "<a href=\"#\" class='Item' onClick=\"javascript:window.open('ects/preview.php?lv=$lvid&language=en','Lehrveranstaltungsinformation','width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes');\">".$p->t('global/englisch')."</a>";
				$need_br=true;
			}
		}

		// Bearbeiten Button anzeigen wenn Lektor der LV und bearbeiten fuer Lektoren aktiviert ist
		// Oder Berechtigung zum Bearbeiten eingetragen ist
		if((!defined('CIS_LEHRVERANSTALTUNG_LVINFO_LEKTOR_EDIT') && $lektor_der_lv)
		  || (defined('CIS_LEHRVERANSTALTUNG_LVINFO_LEKTOR_EDIT') && CIS_LEHRVERANSTALTUNG_LVINFO_LEKTOR_EDIT==true && $lektor_der_lv)
		  || $rechte->isBerechtigt('lehre/lvinfo',$studiengang_kz)
		  || $rechte->isBerechtigtMultipleOe('lehre/lvinfo', $lehrfach_oe_kurzbz_arr)
		  )
		{
			if($need_br)
				$text.= "<br>";
			$text.= "<a href='ects/index.php?lvid=$lvid' target='_blank' class='Item'>".$p->t('lehre/lvInfoBearbeiten')."</a>";
		}
		elseif ($is_lector)
		{
			$text.= "<br>Bearbeiten der LV-Infos derzeit gesperrt";
		}

		$menu[]=array
		(
			'id'=>'core_menu_lvinfo',
			'position'=>'10',
			'name'=>$p->t('lehre/lehrveranstaltungsinformation'),
			'icon'=>'../../../skin/images/button_lvinfo.png',
			'link'=>'',
			'text'=>$text
		);
	}

	// Semesterplan
	if((!defined('CIS_LEHRVERANSTALTUNG_SEMESTERPLAN_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_SEMESTERPLAN_ANZEIGEN) && $angemeldet)
	{
		ensureDirectoryExists($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'semesterplan','teacher');
	  	$dir_empty = isDirectoryEmpty($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'semesterplan');

		if($dir_empty == false)
		{
			$dir_name=$DOC_ROOT.'/documents/'.mb_strtolower($kurzbz).'/'.$semester.'/'.mb_strtolower($short_short_name).'/semesterplan';
			$dest_dir = @dir($dir_name);
			$link = $dest_dir->path.'/';
		}
		else
			$link = '';

		$text='';
		if((!defined('CIS_LEHRVERANSTALTUNG_SEMESTERPLAN_LEKTOR_EDIT') && $user_is_allowed_to_upload)
		 || (defined('CIS_LEHRVERANSTALTUNG_SEMESTERPLAN_LEKTOR_EDIT') && CIS_LEHRVERANSTALTUNG_SEMESTERPLAN_LEKTOR_EDIT==true && $user_is_allowed_to_upload)
		 || $rechte->isBerechtigt('admin',$studiengang_kz) || $rechte->isBerechtigt('lehre',$studiengang_kz))
		{
			$text.= '<a class="Item" href="#" onClick="javascript:window.open(\'semupload.php?lvid='.$lvid.'\',\'_blank\',\'width=400,height=300,location=no,menubar=no,status=no,toolbar=no\');return false;">';
			$text.= $p->t('lehre/semesterplanUpload')."</a>";

			$text.= '&nbsp;&nbsp;&nbsp;<a class="Item" href="semdownhlp.php" >';
			$text.= $p->t('lehre/semesterplanVorlage');
			$text.= ' [hml]';
			$text.= '</a>';
			$text.= '&nbsp;<a class="Item" href="semdownhlp.php?format=doc" >';
			$text.= '[doc]';
			$text.= '</a>';
			$text.= '&nbsp;<a href="#" onClick="showSemPlanHelp()";>('.$p->t('lehre/semesterplanVorlageHilfe').')</a>';
		}

		$menu[]=array
		(
			'id'=>'core_menu_semesterplan',
			'position'=>'20',
			'name'=>$p->t('lehre/semesterplan'),
			'icon'=>'../../../skin/images/button_semplan.png',
			'link'=>$link,
			'link_target'=>'_blank',
			'text'=>$text
		);
	}

	//DOWNLOAD
	if((!defined('CIS_LEHRVERANSTALTUNG_DOWNLOAD_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_DOWNLOAD_ANZEIGEN) && $angemeldet)
	{
		ensureDirectoryExists($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'download','teacher');
	  	$dir_empty = isDirectoryEmpty($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'download');

		$text = '';
		if($dir_empty == false)
		{
			$dir_name=$DOC_ROOT.'/documents/'.mb_strtolower($kurzbz).'/'.$semester.'/'.mb_strtolower($short_short_name).'/download';
			$dest_dir = @dir($dir_name);
			$link = $dest_dir->path.'/';
		}
		else
			$link = '';

		//Wenn user eine Lehrfachzuteilung fuer dieses Lehrfach hat wird
		//Ein Link zum Upload angezeigt und ein Link um das Download-Verzeichnis
		//als Zip Archiv herunterzuladen
		if($user_is_allowed_to_upload || $rechte->isBerechtigt('admin',$studiengang_kz) || $rechte->isBerechtigt('lehre',$studiengang_kz))// || $rechte->isBerechtigt('lehre',null,null,$fachbereich_id))
		{
			$text.= mb_strtolower("$kurzbz/$semester/$short/download");
			$text.=  '<br>';
			$text.=  "<a class='Item' target='_blank' href='upload.php?course_id=$studiengang_kz&term_id=$semester&short=$short'>".$p->t('lehre/upload')."</a>";
			$text.=  '&nbsp;&nbsp;&nbsp;';
			if(isset($dir_empty) && $dir_empty == false)
				$text.=  "<a class='Item' title='".$p->t('lehre/ziparchivTitle')."' href='zipdownload.php?stg=$studiengang_kz&sem=$semester&short=$short' target='_blank'>".$p->t('lehre/ziparchiv')."</a>";
			else
				$text.=  $p->t('lehre/ziparchiv');
		}

		$menu[]=array
		(
			'id'=>'core_menu_download',
			'position'=>'30',
			'name'=>$p->t('lehre/download'),
			'icon'=>'../../../skin/images/button_download.png',
			'link'=>$link,
			'text'=>$text
		);
	}

	// Anwesenheits und Notenlisten
	if(CIS_LEHRVERANSTALTUNG_LEISTUNGSUEBERSICHT_ANZEIGEN || $is_lector)
	{
		$link='';
		$name='';
	  	if($is_lector)
		{
			$name = $p->t('lehre/anwesenheitsUndNotenlisten');
			$link= "anwesenheitsliste.php?stg_kz=$studiengang_kz&sem=$semester&lvid=$lvid&stsem=$angezeigtes_stsem";
		}

		ensureDirectoryExists($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'leistung','teacher');
		$dir_empty = isDirectoryEmpty($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'leistung');

		$text='';
	  	if(CIS_LEHRVERANSTALTUNG_LEISTUNGSUEBERSICHT_ANZEIGEN && ($angemeldet || $is_lector))
		{
			if($dir_empty == false)
			{
				$dir_name=$DOC_ROOT.'/documents/'.mb_strtolower($kurzbz).'/'.$semester.'/'.mb_strtolower($short_short_name).'/leistung';
				$dest_dir = @dir($dir_name);

				if($is_lector)
				{
					$text.= '<a href="'.$dest_dir->path.'" target="_blank">';
					$text.= '<strong>'.$p->t('lehre/leistungsuebersicht').'</strong>';
					$text.= '</a>';
				}
				else
				{
					$name = $p->t('lehre/leistungsuebersicht');
					$link = $dest_dir->path;
				}
			}
			else
			{
				if($is_lector)
				{
					$text.= '<strong>'.$p->t('lehre/leistungsuebersicht').'</strong>';
				}
				else
				{
					$name = $p->t('lehre/leistungsuebersicht');
					$link = '';
				}
			}
		}

		$menu[]=array
		(
			'id'=>'core_menu_anwesenheitslisten',
			'position'=>'40',
			'name'=>$name,
			'icon'=>'../../../skin/images/button_listen.png',
			'link'=>$link,
			'text'=>$text
		);
	}

	//FEEDBACK
	if((!defined('CIS_LEHRVERANSTALTUNG_FEEDBACK_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_FEEDBACK_ANZEIGEN) && $angemeldet)
	{
		$menu[]=array
		(
			'id'=>'core_menu_feedback',
			'position'=>'50',
			'name'=>$p->t('lehre/feedback'),
			'icon'=>'../../../skin/images/button_feedback.png',
			'link'=>'feedback.php?lvid='.$lvid,
			'text'=>''
		);
	}

	// Uebungstool
	if((!defined('CIS_LEHRVERANSTALTUNG_UEBUNGSTOOL_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_UEBUNGSTOOL_ANZEIGEN) && $angemeldet)
	{
		$show=false;
		$link='';
		$link_onclick='';
		$text='';

		//wenn kein Moodle Kurs existiert dann KT anzeigen
		$qry = "SELECT 1 FROM lehre.tbl_moodle WHERE
				(lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER)." AND studiensemester_kurzbz=".$db->db_add_param($angezeigtes_stsem).")
				OR
				(lehreinheit_id IN (SELECT lehreinheit_id FROM lehre.tbl_lehreinheit
									WHERE lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER)." AND
									studiensemester_kurzbz=".$db->db_add_param($angezeigtes_stsem)."))";

		if($result = $db->db_query($qry))
			if($db->db_num_rows($result)==0)
				$show=true;

		//wenn eine Kreuzerlliste existiert dann den Link immer anzeigen
		$qry = "SELECT 1 FROM campus.tbl_uebung
				WHERE lehreinheit_id IN (SELECT lehreinheit_id FROM lehre.tbl_lehreinheit
										WHERE lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER)." AND
										studiensemester_kurzbz=".$db->db_add_param($angezeigtes_stsem).")";
		if($result = $db->db_query($qry))
			if($db->db_num_rows($result)>0)
				$show=true;

		if($show)
		{
			if(isset($angezeigtes_stsem))
				$studiensem = '&stsem='.urlencode($angezeigtes_stsem);
			else
				$studiensem = '';

			//Kreuzerltool
			if($is_lector)
			{
				$link='benotungstool/verwaltung.php?lvid='.urlencode($lvid).$studiensem;
				$text.='<a href="'.APP_ROOT.'cms/dms.php?id='.$p->t('dms_link/benotungstoolHandbuch').'" class="Item" target="_blank">'.$p->t('lehre/benotungstoolHandbuch').' [PDF]</a>';
			}
			else
			{
				$link='benotungstool/studentenansicht.php?lvid='.urlencode($lvid).$studiensem;
			}
		}
		else
		{
			if($is_lector)
			{
				$link='';
				$text='<a href="'.APP_ROOT.'cms/dms.php?id='.$p->t('dms_link/benotungstoolHandbuch').'" class="Item" target="_blank">'.$p->t('lehre/benotungstoolHandbuch').' [PDF]</a>';
				$link_onclick='alert(\''.$p->t('lehre/kreuzerltoolMitMoodleInfo').'\');';
			}
		}

		$menu[]=array
		(
			'id'=>'core_menu_uebungstool',
			'position'=>'60',
			'name'=>$p->t('lehre/kreuzerltool'),
			'icon'=>'../../../skin/images/button_kreuzerltool.png',
			'link'=>$link,
			'link_onclick'=>$link_onclick,
			'text'=>$text
		);
	}


	//Moodle
	$showmoodle=false;
	$link_target='';
	$link_onclick='';
	$text='';
	$link='';

	//Schauen ob Moodle fuer diesen Studiengang freigeschaltet ist
	$qry = "SELECT moodle FROM public.tbl_studiengang JOIN lehre.tbl_lehrveranstaltung USING(studiengang_kz) WHERE lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER);
	if($result = $db->db_query($qry))
	{
		if($row = $db->db_fetch_object($result))
		{
			if($db->db_parse_bool($row->moodle))
			{
				$showmoodle=true;
			}
		}
	}

	if(MOODLE)
	{
		//wenn bereits eine Kreuzerlliste existiert, dann den Moodle link nicht anzeigen
		$qry = "SELECT * FROM campus.tbl_uebung WHERE
				lehreinheit_id IN(SELECT lehreinheit_id FROM lehre.tbl_lehreinheit
									WHERE lehrveranstaltung_id=".$db->db_add_param($lvid, FHC_INTEGER)."
									AND studiensemester_kurzbz=".$db->db_add_param($angezeigtes_stsem).")";
		if($result = $db->db_query($qry))
			if($db->db_num_rows($result)>0)
				$showmoodle=false;

		$moodle = new moodle();
		$moodle->getAll($lvid, $angezeigtes_stsem);
		if(count($moodle->result)>0)
			$showmoodle=true;
	}
	else
		$showmoodle=false;

	if($angemeldet)
	{
	    if($showmoodle )
	    {
		    $link = "moodle_choice.php?lvid=$lvid&stsem=$angezeigtes_stsem";
		    if(count($moodle->result)>0)
		    {
			    if(!$is_lector)
			    {
				    $moodle->result=array();
				    $moodle->getCourse($lvid, $angezeigtes_stsem, $user);

				    if(count($moodle->result)==1)
					    $link = $moodle->getPfad($moodle->result[0]->moodle_version).'course/view.php?id='.$moodle->result[0]->mdl_course_id;
				    else
					    $link = "moodle_choice.php?lvid=$lvid&stsem=$angezeigtes_stsem";
			    }
		    	else
				{
				    if(count($moodle->result)==1)
				    {
					    $link = $moodle->getPfad($moodle->result[0]->moodle_version).'course/view.php?id='.$moodle->result[0]->mdl_course_id;
				    }
				    else
					    $link = "moodle_choice.php?lvid=$lvid&stsem=$angezeigtes_stsem";
				}
				$link_target='_blank';
			}
			else
			{
				$link='';
			}

			if($is_lector &&
				(!defined('CIS_LEHRVERANSTALTUNG_MOODLE_LEKTOR_EDIT')
				|| (defined('CIS_LEHRVERANSTALTUNG_MOODLE_LEKTOR_EDIT') && CIS_LEHRVERANSTALTUNG_MOODLE_LEKTOR_EDIT)
				))
			{
			    $text.= '<a href="moodle2_4_wartung.php?lvid='.$lvid.'&stsem='.$angezeigtes_stsem.'" class="Item">'.$p->t('lehre/moodleWartung').'</a>
					    <br /><a href="'.APP_ROOT.'cms/dms.php?id='.$p->t('dms_link/moodleHandbuch24').'" class="Item" target="_blank">'.$p->t('lehre/moodleHandbuch').'</a>';
			}
	    }
	    else
	    {
		    if($is_lector)
		    {
				$link='';
				$link_onclick='alert(\''.$p->t('lehre/moodleMitKreuzerltoolInfo').'\'); return false';
		    }
	    }
	}
	$menu[]=array
	(
		'id'=>'core_menu_moodle',
		'position'=>'70',
		'name'=>$p->t('lehre/moodle'),
		'icon'=>'../../../skin/images/button_moodle.png',
		'link'=>$link,
		'link_target'=>$link_target,
		'link_onclick'=>$link_onclick,
		'text'=>$text
	);

	//Gesamtnote
	if($is_lector && ((!defined('CIS_LEHRVERANSTALTUNG_GESAMTNOTE_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_GESAMTNOTE_ANZEIGEN) && $angemeldet))
	{
		$menu[]=array
		(
			'id'=>'core_menu_gesamtnote',
			'position'=>'80',
			'name'=>$p->t('lehre/gesamtnote'),
			'icon'=>'../../../skin/images/button_endnote.png',
			'link'=>'benotungstool/lvgesamtnoteverwalten.php?lvid='.urlencode($lvid).'&stsem='.urlencode($angezeigtes_stsem)
		);
	}

	// Studentenupload
	if((!defined('CIS_LEHRVERANSTALTUNG_STUDENTENUPLOAD_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_STUDENTENUPLOAD_ANZEIGEN) && $angemeldet)
	{
		$link='';
		$link_target='';

		ensureDirectoryExists($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'upload','student');
		$dir_empty = isDirectoryEmpty($DOC_ROOT, $kurzbz, $semester, $short_short_name, 'upload');

		if(isset($dir_empty) && $dir_empty == false)
		{
			if($is_lector == true)
			{
				$link='lector_choice.php?lvid='.urlencode($lvid);
				$link_target='_blank';
			}
			else
			{
				$link='upload.php?course_id='.urlencode($studiengang_kz).'&term_id='.urlencode($semester).'&short='.urlencode($short);
				$link_target='_blank';
			}
		}
		else
		{
			if($is_lector == true)
			{
				$link='';
			}
			else
			{
				$link='upload.php?course_id='.urlencode($studiengang_kz).'&term_id='.urlencode($semester).'&short='.urlencode($short);
				$link_target='_blank';
			}
		}
		$menu[]=array
		(
			'id'=>'core_menu_studentenupload',
			'position'=>'90',
			'name'=>$p->t('lehre/studentenAbgabe'),
			'icon'=>'../../../skin/images/button_studiupload.png',
			'link'=>$link,
			'link_target'=>$link_target
		);
	}

	if((!defined('CIS_LEHRVERANSTALTUNG_MAILSTUDIERENDE_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_MAILSTUDIERENDE_ANZEIGEN) && $angemeldet)
	{
		// Email an Studierende

		$mailto='mailto:';

		$qry = "SELECT
					distinct vw_lehreinheit.stg_kurzbz, vw_lehreinheit.stg_typ, vw_lehreinheit.semester,
					COALESCE(vw_lehreinheit.verband,'') as verband, COALESCE(vw_lehreinheit.gruppe,'') as gruppe, vw_lehreinheit.gruppe_kurzbz, tbl_gruppe.mailgrp
				FROM
					campus.vw_lehreinheit
					LEFT JOIN public.tbl_gruppe USING(gruppe_kurzbz)
				WHERE
					lehrveranstaltung_id=".$db->db_add_param($lvid)."
					AND studiensemester_kurzbz=".$db->db_add_param($angezeigtes_stsem);
		$nomail='';
		$variable = new variable();
		$variable->loadVariables($user);

		if($result = $db->db_query($qry))
		{
			while($row = $db->db_fetch_object($result))
			{
				if($row->gruppe_kurzbz!='')
				{
					if(!$db->db_parse_bool($row->mailgrp))
					{
						$nomail=$row->gruppe_kurzbz.' ';
					}
					else
						$mailto.=mb_strtolower($row->gruppe_kurzbz.'@'.DOMAIN.$variable->variable->emailadressentrennzeichen);
				}
				else
					$mailto.=mb_strtolower($row->stg_typ.$row->stg_kurzbz.$row->semester.trim($row->verband).trim($row->gruppe).'@'.DOMAIN.$variable->variable->emailadressentrennzeichen);
			}
		}

		if($nomail!='')
			$link_onclick='alert(\''.$p->t('lehre/keinMailverteiler',array($nomail)).'\');';
		else
			$link_onclick='';

		$menu[]=array
		(
			'id'=>'core_menu_mailanstudierende',
			'position'=>'100',
			'name'=>$p->t('lehre/mail'),
			'icon'=>'../../../skin/images/button_feedback.png',
			'link'=>$mailto,
			'link_onclick'=>$link_onclick
		);
	}

	// Pinboard
	if((!defined('CIS_LEHRVERANSTALTUNG_PINBOARD_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_PINBOARD_ANZEIGEN) && $angemeldet)
	{
		$text='';
		if($is_lector)
			$text.= "<a href='../../../cms/newsverwaltung.php?studiengang_kz=$studiengang_kz&semester=$semester' class='Item'>".$p->t('profil/adminstration')."</a>";

		$menu[]=array
		(
			'id'=>'core_menu_pinboard',
			'position'=>'110',
			'name'=>$p->t('lehre/pinboard'),
			'icon'=>'../../../skin/images/button_pinboard.png',
			'link'=>'../../../cms/news.php?studiengang_kz='.urlencode($studiengang_kz).'&semester='.urlencode($semester),
			'text'=>$text
		);

	}

	if(!defined('CIS_LEHRVERANSTALTUNG_ABMELDUNG_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_ABMELDUNG_ANZEIGEN)
	{
		if(!$is_lector)
		{
			$lvangebot = new lvangebot();
			$gruppen = $lvangebot->AbmeldungMoeglich($lvid, $angezeigtes_stsem, $user);
			if(count($gruppen)>0)
			{
				$menu[]=array
				(
					'id'=>'core_menu_abmeldung',
					'position'=>'120',
					'name'=>$p->t('lehre/abmelden'),
					'icon'=>'../../../skin/images/button_studiupload.png',
					'link'=>'abmeldung.php?lvid='.urlencode($lvid).'&stsem='.urlencode($angezeigtes_stsem),
				);

			}
		}
	}

	//Anzeigen von zusaetzlichen Lehre-Tools
	$lehretools = new lehre_tools();
	if($lehretools->getTools($lvid, $angezeigtes_stsem))
	{
		if(count($lehretools->result)>0)
		{
			foreach($lehretools->result as $row)
			{
				$menu[]=array
				(
					'id'=>'core_menu_lehretools_'.$row->lehre_tools_id,
					'position'=>'1000',
					'name'=>$row->bezeichnung[$sprache],
					'icon'=>'../../../cms/dms.php?id='.$row->logo_dms_id,
					'link'=>$row->basis_url,
					'link_target'=>'_blank'
				);
			}
		}
	}


//************* Menuepunkte anzeigen ****************

	// Addons Menuepunkte laden
	require_once(dirname(__FILE__).'/../addon.class.php');
	$addon_obj = new addon();
	if($addon_obj->loadAddons())
	{
		if(count($addon_obj->result)>0)
		{
			foreach($addon_obj->result as $row)
			{
				if(file_exists(dirname(__FILE__).'/../../addons/'.$row->kurzbz.'/cis/menu_lv.inc.php'))
				{
					include(dirname(__FILE__).'/../../addons/'.$row->kurzbz.'/cis/menu_lv.inc.php');
				}
			}
		}
	}

	// Menue sortieren
	foreach ($menu as $key => $row)
	    $pos[$key] = $row['position'];

	array_multisort($pos, SORT_ASC, SORT_NUMERIC, $menu);

	//var_dump($menu);

	foreach($menu as $row)
	{
		checkZeilenUmbruch();
		$eintraegeprozeile++;
		echo '<td class="tdvertical" align="center">';

		if(isset($row['icon']))
		{
			if(isset($row['link']) && $row['link']!='')
			{
				echo '<a href="'.$row['link'].'"';
				if(isset($row['link_target']) && $row['link_target']!='')
					echo ' target="'.$row['link_target'].'"';
				if(isset($row['link_onclick']) && $row['link_onclick']!='')
					echo ' onclick="'.$row['link_onclick'].'"';
				echo '>';
			}
			echo '<img class="lv" src="'.$row['icon'].'">';
			echo '<br>';
			if(isset($row['name']))
				echo '<b>'.$row['name'].'</b>';
			if(isset($row['link']) && $row['link']!='')
				echo '</a>';
		}
		echo '<br>';
		if(isset($row['text']))
			echo $row['text'];

		echo '</td>';
	}

/**
 * Prueft ob ein Verzeichnis vorhanden ist, wenn nicht wird das Verzeichnis
 * erstellt und die Rechte gesetzt
 */
function ensureDirectoryExists($DOC_ROOT, $stg, $semester, $short_short_name, $type,$role)
{
	$dir_name=$DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name).'/'.$type;

	$dest_dir = @dir($dir_name);
	if(!@is_dir($dest_dir->path))
	{
		if(!@is_dir(DOC_ROOT.'/documents/'.mb_strtolower($stg)))
		{
			exec('mkdir -m 755 '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg)));
			exec('sudo chown www-data:teacher '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg)));
		}
		if(!@is_dir(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester))
		{
			exec('mkdir -m 755 '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester));
			exec('sudo chown www-data:teacher '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester));
		}
		if(!@is_dir(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name)))
		{
			exec('mkdir -m 755 '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name)));
			exec('sudo chown www-data:teacher '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name)));
		}
		if(!@is_dir(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name).'/'.$type))
		{
			exec('mkdir -m 775 '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name).'/'.$type));
			exec('sudo chown www-data:'.$role.' '.escapeshellarg(DOC_ROOT.'/documents/'.mb_strtolower($stg).'/'.$semester.'/'.mb_strtolower($short_short_name).'/'.$type));
		}
	}
}

/**
 * Prueft ob das Verzeichnis leer ist
 * @return true wenn leer, false wenn nicht
 */
function isDirectoryEmpty($DOC_ROOT, $kurzbz, $semester, $short_short_name, $type)
{
	$dir_name=$DOC_ROOT.'/documents/'.mb_strtolower($kurzbz).'/'.$semester.'/'.mb_strtolower($short_short_name).'/'.$type;
	$dest_dir = @dir($dir_name);

	if($dest_dir)
	{
	  while($entry = $dest_dir->read())
		  if($entry != "." && $entry != "..")
			  return false;
	}
	return true;
}
  ?>
	</tr>
</table>
