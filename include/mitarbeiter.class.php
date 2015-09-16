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
require_once(dirname(__FILE__).'/person.class.php');
require_once(dirname(__FILE__).'/benutzer.class.php');
require_once(dirname(__FILE__).'/functions.inc.php');

class mitarbeiter extends benutzer
{
	public $new;
	public $errormsg;
	public $result=array();

    //Tabellenspalten
	public $ausbildungcode;	//integer
	public $personalnummer;	//serial
	public $kurzbz;			//varchar(8)
	public $lektor;			//boolean
	public $fixangestellt;		//boolean
	public $standort_id;   //varchar(16)
	public $telefonklappe;		//varchar(25)
	public $ort_kurzbz;		//varchar(8)
	public $ext_id_mitarbeiter;	//bigint
	public $stundensatz;
	public $anmerkung;
	public $bismelden;
	public $kleriker;
	public $vorgesetzte=array();
	public $untergebene=array();

	/**
	 * Konstruktor - laedt optional einen Mitarbeiter
	 * @param $uid Mitarbeiter der geladen werden soll (default=null)
	 */
	public function __construct($uid=null)
	{
		parent::__construct();

		//Mitarbeiter laden
		if($uid!=null)
			$this->load($uid);
	}

	/**
	 * Laedt einen Mitarbeiter
	 *
	 * @param $uid
	 * @return true wenn ok, sonst false
	 */
	public function load($uid)
	{
		if(!benutzer::load($uid))
			return false;

		$qry = "SELECT * FROM public.tbl_mitarbeiter LEFT JOIN campus.tbl_resturlaub USING(mitarbeiter_uid)
				WHERE mitarbeiter_uid=".$this->db_add_param($uid).";";

		if($this->db_query($qry))
		{
			if($row = $this->db_fetch_object())
			{
				$this->ausbildungcode = $row->ausbildungcode;
				$this->personalnummer = $row->personalnummer;
				$this->kurzbz = $row->kurzbz;
				$this->lektor = $this->db_parse_bool($row->lektor);
				$this->fixangestellt = $this->db_parse_bool($row->fixangestellt);
				$this->standort_id = $row->standort_id;
				$this->telefonklappe = $row->telefonklappe;
				$this->ort_kurzbz = $row->ort_kurzbz;
				$this->stundensatz = $row->stundensatz;
				$this->anmerkung = $row->anmerkung;
				$this->ext_id_mitarbeiter = $row->ext_id;
				$this->bismelden = $this->db_parse_bool($row->bismelden);
				$this->kleriker = $this->db_parse_bool($row->kleriker);

				$this->urlaubstageprojahr = $row->urlaubstageprojahr;
				$this->resturlaubstage = $row->resturlaubstage;
				return true;
			}
			else
			{
				$this->errormsg = "Kein Eintrag gefunden fuer den Benutzer\n";
				return false;
			}
		}
		else
		{
			$this->errormsg = "Fehler beim Laden der Daten\n";
			return false;
		}
	}

	/**
	 * ueberprueft die Variablen auf Gueltigkeit
	 * @return true wenn gueltig, false im Fehlerfall
	 */
	protected function validate()
	{
		//if(mb_strlen($this->uid)>16)
		//{
		//	$this->errormsg = "ID darf nicht laenger als 16 Zeichen sein\n";
		//	return false;
		//}
		if($this->uid=='')
		{
			$this->errormsg = "UID muss eingegeben werden ".$this->personalnummer."\n";
			return false;
		}
		if($this->ausbildungcode!='' && !is_numeric($this->ausbildungcode))
		{
			$this->errormsg = "Ausbildungscode ist ungueltig\n";
			return false;
		}
		if($this->personalnummer!='' && !is_numeric($this->personalnummer))
		{
			$this->errormsg = "Personalnummer muss eine gueltige Zahl sein\n";
			return false;
		}
		if(mb_strlen($this->kurzbz)>8)
		{
			$this->errormsg = "kurzbz darf nicht laenger als 8 Zeichen sein\n";
			return false;
		}
		if(mb_strlen($this->ort_kurzbz)>16)
		{
			$this->errormsg = "Ort_kurzbz darf nicht laenger als 16 Zeichen sein\n";
			return false;
		}
		if(!is_bool($this->lektor))
		{
			$this->errormsg = "Lektor muss boolean sein ";
			return false;
		}
		if(!is_bool($this->fixangestellt))
		{
			$this->errormsg = "fixangestellt muss boolean sein\n";
			return false;
		}
		if(mb_strlen($this->telefonklappe)>25)
		{
			$this->errormsg = "telefonklappe darf nicht laenger als 25 Zeichen sein\n";
			return false;
		}
		if(mb_strlen($this->updatevon)>32)
		{
			$this->errormsg = "updatevon darf nicht laenger als 32 Zeichen sein\n";
			return false;
		}

		return true;
	}


	/**
	 * Speichert die Mitarbeiterdaten in die Datenbank
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save($new=null, $savebenutzer=true)
	{
		//Variablen checken
		if(!$this->validate())
			return false;

		$this->db_query('BEGIN;');

		if($new==null)
			$new = $this->new;

		if($savebenutzer)
		{
			//Basisdaten speichern
			if(!benutzer::save())
			{
				$this->db_query('ROLLBACK;');
				return false;
			}
		}

		if($new)
		{
		    if(defined('FAS_PERSONALNUMMER_GENERATE_NULL') && FAS_PERSONALNUMMER_GENERATE_NULL)
		    {
			$this->personalnummer = NULL;
		    }
		    else
		    {
			if($this->personalnummer=='')
			{
				do
				{
					//Wenn keine Personalnummer angegeben wurde, dann die naechste freie Suchen
					$qry = "SELECT nextval('public.tbl_mitarbeiter_personalnummer_seq') as id;";
					if($this->db_query($qry))
					{
						if($row = $this->db_fetch_object())
						{
							$personalnummer = $row->id;
						}
						else
						{
							$this->errormsg = 'Fehler beim Ermitteln der Personalnummer';
							return false;
						}
					}
					else
					{
						$this->errormsg = 'Fehler beim Ermitteln der Personalnummer';
						return false;
					}

					//Da die Personalnummer auch direkt uebergeben werden kann, ist es moeglich, dass die Personalnummer
					//aus dem Serial schon vergeben ist. Deshalb wird zur Sicherheit nochmal ueberprueft ob die Nr
					//noch frei ist.
					$qry = "SELECT personalnummer FROM public.tbl_mitarbeiter WHERE personalnummer=".$this->db_add_param($personalnummer, FHC_INTEGER).';';
					if($this->db_query($qry))
					{
						if($this->db_num_rows()==0)
							$this->personalnummer = $personalnummer;
					}

				} while($this->personalnummer=='');
			}
			else
			{
				//Pruefen ob Personalnummer eine gueltige Zahl ist
				if(!is_numeric($this->personalnummer))
				{
					$this->errormsg = 'Personalnummer muss eine gueltige Zahl sein';
					return false;
				}

				//Preufen ob die Personalnummer schon vergeben ist
				$qry = "SELECT personalnummer FROM public.tbl_mitarbeiter WHERE personalnummer=".$this->db_add_param($this->personalnummer, FHC_INTEGER).';';
				if($this->db_query($qry))
				{
					if($this->db_num_rows()!=0)
					{
						$this->errormsg = 'Personalnummer ist bereits vergeben!';
						return false;
					}
				}
			}
		    }
			//Neuen Datensatz anlegen
			$qry = "INSERT INTO public.tbl_mitarbeiter(mitarbeiter_uid, ausbildungcode, personalnummer, kurzbz, lektor, ort_kurzbz,
			                    fixangestellt, standort_id, telefonklappe, anmerkung, stundensatz, updateamum, updatevon, insertamum, insertvon, bismelden,kleriker)

			        VALUES(".$this->db_add_param($this->uid).",".
			 	 	$this->db_add_param($this->ausbildungcode, FHC_INTEGER).",".
			 	 	$this->db_add_param($this->personalnummer, FHC_INTEGER).",".
			 	 	$this->db_add_param($this->kurzbz).','.
			 	 	$this->db_add_param($this->lektor, FHC_BOOLEAN).','.
			 	 	$this->db_add_param($this->ort_kurzbz).','.
					$this->db_add_param($this->fixangestellt,FHC_BOOLEAN).','.
					$this->db_add_param($this->standort_id, FHC_INTEGER).','.
					$this->db_add_param($this->telefonklappe).','.
					$this->db_add_param($this->anmerkung).','.
					$this->db_add_param($this->stundensatz).','.
					$this->db_add_param($this->updateamum).','.
					$this->db_add_param($this->updatevon).', '.
					$this->db_add_param($this->insertamum).','.
					$this->db_add_param($this->insertvon).', '.
					$this->db_add_param($this->bismelden, FHC_BOOLEAN).','.
					$this->db_add_param($this->kleriker, FHC_BOOLEAN).');';
		}
		else
		{
			//Bestehenden Datensatz updaten
			$qry = 'UPDATE public.tbl_mitarbeiter SET'.
			       ' ausbildungcode='.$this->db_add_param($this->ausbildungcode, FHC_INTEGER).','.
			       " personalnummer=".$this->db_add_param($this->personalnummer, FHC_INTEGER).",". //TODO: in Produktivversion nicht angeben
			       ' kurzbz='.$this->db_add_param($this->kurzbz).','.
			       ' lektor='.$this->db_add_param($this->lektor, FHC_BOOLEAN).','.
			       ' fixangestellt='.$this->db_add_param($this->fixangestellt, FHC_BOOLEAN).','.
			       ' bismelden='.$this->db_add_param($this->bismelden, FHC_BOOLEAN).','.
				   ' kleriker='.$this->db_add_param($this->kleriker, FHC_BOOLEAN).','.
			       ' standort_id='.$this->db_add_param($this->standort_id, FHC_INTEGER).','.
			       ' telefonklappe='.$this->db_add_param($this->telefonklappe).','.
			       ' ort_kurzbz='.$this->db_add_param($this->ort_kurzbz).','.
			       ' anmerkung='.$this->db_add_param($this->anmerkung).','.
			       ' stundensatz='.$this->db_add_param($this->stundensatz).','.
			       ' updateamum='.$this->db_add_param($this->updateamum).','.
			       ' updatevon='.$this->db_add_param($this->updatevon).
			       " WHERE mitarbeiter_uid=".$this->db_add_param($this->uid).";";
		}

		if($this->db_query($qry))
		{
			$this->db_query('COMMIT;');
			//Log schreiben
			return true;
		}
		else
		{
			$this->db_query('ROLLBACK;');
			$this->errormsg = "Fehler beim Speichern des Mitarbeiter-Datensatzes";
			return false;
		}
	}

	/**
	 * gibt array mit allen Mitarbeitern zurueck
	 * @return array mit Mitarbeitern
	 */
	public function getMitarbeiter($lektor=true,$fixangestellt=null,$stg_kz=null)
	{
		$sql_query='SELECT DISTINCT campus.vw_mitarbeiter.uid, titelpre, titelpost, vorname, vornamen, nachname, gebdatum, gebort, gebzeit, anmerkung, aktiv,
					homepage, campus.vw_mitarbeiter.updateamum, campus.vw_mitarbeiter.updatevon, personalnummer, kurzbz, lektor, fixangestellt, standort_id, telefonklappe FROM campus.vw_mitarbeiter
					LEFT OUTER JOIN public.tbl_benutzerfunktion USING (uid)
					WHERE TRUE';

		if (!is_null($lektor))
		{
			$sql_query.=' AND';
			if (!$lektor)
				$sql_query.=' NOT';
			$sql_query.=' lektor';
		}

		if (!is_null($fixangestellt))
		{
			$sql_query.=' AND';
			if (!$fixangestellt)
				$sql_query.=' NOT';
			$sql_query.=' fixangestellt';
		}

		if (!is_null($stg_kz))
		{
			$stg = new studiengang($stg_kz);
			$sql_query.=" AND oe_kurzbz=".$this->db_add_param($stg->oe_kurzbz);
		}

		$sql_query.=' ORDER BY nachname, vornamen, kurzbz;';

		if(!$this->db_query($sql_query))
		{
			$this->errormsg=$this->db_last_error();
			return false;
		}
		$num_rows=$this->db_num_rows();
		$result=array();
		for($i=0;$i<$num_rows;$i++)
		{
   			$row=$this->db_fetch_object(null,$i);
			$l=new mitarbeiter();
			// Personendaten
			$l->uid=$row->uid;
			$l->titelpre=$row->titelpre;
			$l->titelpost=$row->titelpost;
			$l->vorname=$row->vorname;
			$l->vornamen=$row->vornamen;
			$l->nachname=$row->nachname;
			$l->gebdatum=$row->gebdatum;
			$l->gebort=$row->gebort;
			$l->gebzeit=$row->gebzeit;
			//$l->foto=$row->foto;
			$l->anmerkung=$row->anmerkung;
			$l->aktiv= $this->db_parse_bool($row->aktiv);
			$l->homepage=$row->homepage;
			$l->updateamum=$row->updateamum;
			$l->updatevon=$row->updatevon;
			// Lektorendaten
			$l->personalnummer=$row->personalnummer;
			$l->kurzbz=$row->kurzbz;
			$l->lektor= $this->db_parse_bool($row->lektor);
			$l->fixangestellt= $this->db_parse_bool($row->fixangestellt);
			$l->standort_id = $row->standort_id;
			$l->telefonklappe=$row->telefonklappe;

			// Lektor in Array speichern
			$result[]=$l;
		}
		return $result;
	}

	/**
	 * Liefert Mitarbeiter, die eine Funktion in den uebergebenen Studiengaengen haben.
	 * Optional datum_von und datum_bis fuer die Funktion ansonsten now()
	 *
	 * @param $lektor
	 * @param $fixangestellt
	 * @param $stge Array mit Studiengaengen
	 * @param $fkt_kurzbz
	 * @param $order
	 * @param $datum_von
	 * @param $datum_bis
	 * @return boolean
	 */
	public function getMitarbeiterStg($lektor=true,$fixangestellt, $stge, $fkt_kurzbz, $order='studiengang_kz, nachname, vorname, kurzbz', $datum_von='', $datum_bis='')
	{
		$sql_query='SELECT DISTINCT campus.vw_mitarbeiter.*, studiengang_kz FROM campus.vw_mitarbeiter
					JOIN public.tbl_benutzerfunktion USING (uid) JOIN public.tbl_studiengang USING(oe_kurzbz)
					WHERE true';
		if(!is_null($lektor))
		{
			$sql_query.=' AND';
			if (!$lektor)
				$sql_query.=' NOT';
			$sql_query.=' lektor';
		}

		if ($fixangestellt!=null)
		{
			$sql_query.=' AND';
			if (!$fixangestellt)
				$sql_query.=' NOT';
			$sql_query.=' fixangestellt';
		}
		if($fkt_kurzbz!='')
		{
			$sql_query.=" AND funktion_kurzbz=".$this->db_add_param($fkt_kurzbz);
		}
		if(!is_null($datum_von))
		{
			if($datum_von=='')
				$sql_query.=" AND (tbl_benutzerfunktion.datum_von IS NULL OR tbl_benutzerfunktion.datum_von<=now())";
			else
				$sql_query.=" AND (tbl_benutzerfunktion.datum_von IS NULL OR tbl_benutzerfunktion.datum_von<=".$this->db_add_param($datum_von).")";
		}
		if(!is_null($datum_bis))
		{
			if($datum_bis=='')
				$sql_query.=" AND (tbl_benutzerfunktion.datum_bis IS NULL OR tbl_benutzerfunktion.datum_bis>=now())";
			else
				$sql_query.=" AND (tbl_benutzerfunktion.datum_bis IS NULL OR tbl_benutzerfunktion.datum_bis>=".$this->db_add_param($datum_bis).")";
		}
		if ($stge!=null)
		{
			$in='';
			foreach ($stge as $stg)
			{
				$in.=','.$this->db_add_param($stg, FHC_INTEGER);
				if($stg==0)
				{
					$in='';
					break;
				}
			}
			if($in!='')
				$sql_query.=' AND studiengang_kz in (-1'.$in.')';
		}
	    $sql_query.=' ORDER BY '.$order.';';
	    //echo $sql_query;

		if(!$this->db_query($sql_query))
		{
			$this->errormsg=$this->db_last_error();
			return false;
		}

		$num_rows=$this->db_num_rows();
		$result=array();
		for($i=0;$i<$num_rows;$i++)
		{
   			$row=$this->db_fetch_object(null,$i);
			$l=new mitarbeiter();
			// Personendaten
			$l->uid=$row->uid;
			$l->titelpre=$row->titelpre;
			$l->titelpost=$row->titelpost;
			$l->vorname=$row->vorname;
			$l->vornamen=$row->vornamen;
			$l->nachname=$row->nachname;
			$l->gebdatum=$row->gebdatum;
			$l->gebort=$row->gebort;
			$l->gebzeit=$row->gebzeit;
			$l->foto=$row->foto;
			$l->anmerkung=$row->anmerkung;
			$l->aktiv= $this->db_parse_bool($row->aktiv);
			//$l->bismelden=$row->bismelden=='t'?true:false;
			$l->homepage=$row->homepage;
			$l->updateamum=$row->updateamum;
			$l->updatevon=$row->updatevon;
			// Lektorendaten
			$l->personalnummer=$row->personalnummer;
			$l->kurzbz=$row->kurzbz;
			$l->lektor= $this->db_parse_bool($row->lektor);
			$l->fixangestellt= $this->db_parse_bool($row->fixangestellt);
			$l->standort_id = $row->standort_id;
			$l->telefonklappe=$row->telefonklappe;
			$l->studiengang_kz = $row->studiengang_kz;
			//$l->ort_kurzbz=$row->ort_kurzbz;
			// Lektor in Array speichern
			$result[]=$l;
		}
		return $result;
	}

	/**
	 * Liefert die Mitarbeiter, die im angegebenen Intervall eine Zeitsperre eingetragen haben.
	 * UID optional um nur einen bestimmten Mitarbeiter abzufragen.
	 *
	 * @param $von
	 * @param $bis
	 * @param $uid Optional
	 * @return boolean
	 */
	public function getMitarbeiterZeitsperre($von,$bis,$uid=null)
	{
		$sql_query="SELECT DISTINCT nachname, vorname, uid,titelpre, titelpost, vornamen
				FROM campus.vw_mitarbeiter JOIN campus.tbl_zeitsperre ON (uid=mitarbeiter_uid)
				WHERE ((".$this->db_add_param($von)."<=bisdatum AND ".$this->db_add_param($bis).">=bisdatum) OR (".$this->db_add_param($bis).">=vondatum AND ".$this->db_add_param($von)."<=vondatum))";
	    if($uid!=null)
			$sql_query .= " AND mitarbeiter_uid=".$this->db_add_param($uid);

		$sql_query .= " ORDER BY nachname;";

		if(!$this->db_query($sql_query))
		{
			$this->errormsg=$this->db_last_error();
			return false;
		}

		$num_rows=$this->db_num_rows();
		$result=array();
		for($i=0;$i<$num_rows;$i++)
		{
   			$row=$this->db_fetch_object(null,$i);
			$l=new mitarbeiter();
			// Personendaten
			$l->uid=$row->uid;
			$l->titelpre=$row->titelpre;
			$l->titelpost=$row->titelpost;
			$l->vorname=$row->vorname;
			$l->vornamen=$row->vornamen;
			$l->nachname=$row->nachname;

			// Lektor in Array speichern
			$result[]=$l;
		}
		return $result;
	}

	/**
	 * Laedt alle Mitarbeiter einer Lehreinheit
	 * @param lehreinheit_id
	 * @return true wenn ok, false wenn Fehler
	 */
	public function getMitarbeiterFromLehreinheit($lehreinheit_id)
	{
		if(!is_numeric($lehreinheit_id))
		{
			$this->errormsg = 'Lehreinheit_id ist ungueltig';
			return false;
		}

		$qry = "SELECT uid, vorname, vornamen, nachname, titelpre, titelpost, kurzbz FROM lehre.tbl_lehreinheitmitarbeiter JOIN campus.vw_mitarbeiter ON(mitarbeiter_uid=uid)
				WHERE lehreinheit_id=".$this->db_add_param($lehreinheit_id, FHC_INTEGER).';';

		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new mitarbeiter();

				$obj->uid = $row->uid;
				$obj->vorname = $row->vorname;
				$obj->nachname = $row->nachname;
				$obj->titelpre = $row->titelpre;
				$obj->titelpost = $row->titelpost;
				$obj->kurzbz = $row->kurzbz;
				$obj->vornamen = $row->vornamen;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt alle Mitarbeiter einer Lehrveranstaltung
	 * @param lehrveranstaltung_id
	 * @return true wenn ok, false wenn Fehler
	 */
	public function getMitarbeiterFromLehrveranstaltung($lehrveranstaltung_id)
	{
		if(!is_numeric($lehrveranstaltung_id))
		{
			$this->errormsg = 'Lehrveranstaltung_id ist ungueltig';
			return false;
		}

		$qry = "SELECT uid, vorname, vornamen, nachname, titelpre, titelpost, kurzbz FROM lehre.tbl_lehreinheitmitarbeiter, campus.vw_mitarbeiter, lehre.tbl_lehreinheit
				WHERE lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER)." AND mitarbeiter_uid=uid AND tbl_lehreinheitmitarbeiter.lehreinheit_id=tbl_lehreinheit.lehreinheit_id;";

		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new mitarbeiter();

				$obj->uid = $row->uid;
				$obj->vorname = $row->vorname;
				$obj->nachname = $row->nachname;
				$obj->titelpre = $row->titelpre;
				$obj->titelpost = $row->titelpost;
				$obj->kurzbz = $row->kurzbz;
				$obj->vornamen = $row->vornamen;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt das Personal
	 *
	 * @param $fix wenn true werden nur fixangestellte geladen
	 * @param $stgl wenn true werden nur studiengangsleiter geladen
	 * @param $fbl wenn true werden nur fachbereichsleiter geladen
	 * @param $aktiv wenn true werden nur aktive geladen, wenn false dann nur inaktve, wenn null dann alle
	 * @param $karenziert wenn true werden alle geladen die karenziert sind
	 * @param $verwendung wenn true werden alle geladen die eine BIS-Verwendung eingetragen haben
	 * @return boolean
	 */
	public function getPersonal($fix, $stgl, $fbl, $aktiv, $karenziert, $verwendung, $vertragnochnichtretour=null)
	{
		$qry = "SELECT distinct on(mitarbeiter_uid) *, tbl_benutzer.aktiv as aktiv, tbl_mitarbeiter.insertamum, tbl_mitarbeiter.insertvon, tbl_mitarbeiter.updateamum, tbl_mitarbeiter.updatevon FROM ((public.tbl_mitarbeiter JOIN public.tbl_benutzer ON(mitarbeiter_uid=uid)) JOIN public.tbl_person USING(person_id)) LEFT JOIN public.tbl_benutzerfunktion USING(uid) LEFT JOIN campus.tbl_resturlaub USING(mitarbeiter_uid) WHERE true";

		if($fix=='true')
			$qry .= " AND fixangestellt=true";
		if($fix=='false')
			$qry .= " AND fixangestellt=false";
		if($stgl)
		{
			$qry .= " AND funktion_kurzbz='Leitung' AND EXISTS(SELECT studiengang_kz FROM public.tbl_studiengang WHERE oe_kurzbz=tbl_benutzerfunktion.oe_kurzbz)
					  AND (tbl_benutzerfunktion.datum_von is null OR tbl_benutzerfunktion.datum_von<=now()) AND
						  (tbl_benutzerfunktion.datum_bis is null OR tbl_benutzerfunktion.datum_bis>=now())";
		}
		if($fbl)
		{
			$qry .= " AND funktion_kurzbz='Leitung' AND EXISTS(SELECT fachbereich_kurzbz FROM public.tbl_fachbereich fb WHERE oe_kurzbz=tbl_benutzerfunktion.oe_kurzbz AND fb.aktiv)
					  AND (tbl_benutzerfunktion.datum_von is null OR tbl_benutzerfunktion.datum_von<=now()) AND
						  (tbl_benutzerfunktion.datum_bis is null OR tbl_benutzerfunktion.datum_bis>=now())";
		}
		if($aktiv=='true')
			$qry .= " AND tbl_benutzer.aktiv=true";
		if($aktiv=='false')
			$qry .= " AND tbl_benutzer.aktiv=false";
		if($karenziert)
			$qry .= " AND uid IN (SELECT mitarbeiter_uid FROM bis.tbl_bisverwendung WHERE beschausmasscode='5' AND (ende>now() OR ende is null))"; //beginn<(SELECT start FROM public.tbl_studiensemester WHERE studiensemester_kurzbz='$studiensemester_kurzbz') AND ende<(SELECT ende FROM public.tbl_studiensemester WHERE studiensemester_kurzbz='$studiensemester_kurzbz')
		if($verwendung=='true')
		{
			$qry.=" AND EXISTS(SELECT * FROM bis.tbl_bisverwendung WHERE (ende>now() or ende is null) AND tbl_bisverwendung.mitarbeiter_uid=tbl_mitarbeiter.mitarbeiter_uid)";
		}
		if($verwendung=='false')
		{
			$qry.=" AND NOT EXISTS(SELECT * FROM bis.tbl_bisverwendung WHERE (ende>now() or ende is null) AND tbl_bisverwendung.mitarbeiter_uid=tbl_mitarbeiter.mitarbeiter_uid)";
		}
		if($vertragnochnichtretour=='true')
		{
			$qry.=" AND EXISTS(SELECT * FROM lehre.tbl_vertrag
						WHERE person_id=tbl_benutzer.person_id
						AND NOT EXISTS(SELECT 1 FROM lehre.tbl_vertrag_vertragsstatus WHERE vertrag_id=tbl_vertrag.vertrag_id AND vertragsstatus_kurzbz='retour')
						AND NOT EXISTS(SELECT 1 FROM lehre.tbl_vertrag_vertragsstatus WHERE vertrag_id=tbl_vertrag.vertrag_id AND vertragsstatus_kurzbz='abgerechnet')
						)";
		}

        $qry.=';';
		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new mitarbeiter();

				$obj->person_id = $row->person_id;
				$obj->staatsbuergerschaft = $row->staatsbuergerschaft;
				$obj->geburtsnation = $row->geburtsnation;
				$obj->sprache = $row->sprache;
				$obj->anrede = $row->anrede;
				$obj->titelpost = $row->titelpost;
				$obj->titelpre = $row->titelpre;
				$obj->nachname = $row->nachname;
				$obj->vorname = $row->vorname;
				$obj->vornamen = $row->vornamen;
				$obj->gebdatum = $row->gebdatum;
				$obj->gebort = $row->gebort;
				$obj->gebzeit = $row->gebzeit;
				$obj->anmerkung = $row->anmerkung;
				$obj->homepage = $row->homepage;
				$obj->svnr = $row->svnr;
				$obj->ersatzkennzeichen = $row->ersatzkennzeichen;
				$obj->familienstand = $row->familienstand;
				$obj->geschlecht = $row->geschlecht;
				$obj->anzahlkinder = $row->anzahlkinder;
				$obj->bnaktiv = $this->db_parse_bool($row->aktiv);
				$obj->uid = $row->uid;
				$obj->personalnummer = $row->personalnummer;
				$obj->telefonklappe = $row->telefonklappe;
				$obj->kurzbz = $row->kurzbz;
				$obj->lektor = $this->db_parse_bool($row->lektor);
				$obj->fixangestellt = $this->db_parse_bool($row->fixangestellt);
				$obj->bismelden = $this->db_parse_bool($row->bismelden);
				$obj->stundensatz = $row->stundensatz;
				$obj->ausbildungcode = $row->ausbildungcode;
				$obj->ort_kurzbz = $row->ort_kurzbz;
				$obj->standort_id = $row->standort_id;
				$obj->anmerkung = $row->anmerkung;
				$obj->alias = $row->alias;
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;

				$obj->urlaubstageprojahr = $row->urlaubstageprojahr;
				$obj->resturlaubstage = $row->resturlaubstage;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Prueft ob die Kurzbz bereits existiert
	 */
	public function kurzbz_exists($kurzbz)
	{
		$qry = "SELECT * FROM public.tbl_mitarbeiter WHERE kurzbz=".$this->db_add_param($kurzbz).';';

		if($this->db_query($qry))
		{
			if($this->db_num_rows()>0)
			{
				$this->errormsg = '';
				return true;
			}
			else
			{
				$this->errormsg = '';
				return false;
			}

		}
		else
		{
			$this->errormsg = 'Fehler bei DatenbankAbfrage';
			return false;
		}

	}

	/**
	 * Laedt die Mitarbeiter deren
	 * Nachname oder uid mit $filter beginnt
	 */
	public function getMitarbeiterFilter($filter)
	{
		$qry = "SELECT * FROM campus.vw_mitarbeiter WHERE lower(nachname) ~* lower(".$this->db_add_param($filter).") OR uid ~* ".$this->db_add_param($filter).';';
		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new mitarbeiter();

				$obj->uid = $row->uid;
				$obj->vorname = $row->vorname;
				$obj->nachname = $row->nachname;
				$obj->titelpre = $row->titelpre;
				$obj->titelpost = $row->titelpost;
				$obj->kurzbz = $row->kurzbz;
				$obj->vornamen = $row->vornamen;

				$this->result[] = $obj;
			}

			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Sucht die Mitarbeiter deren
	 * Nachname, Vorname, UID $filter enthaelt
	 * @param $filter
	 */
	public function search($filter, $limit=null, $aktiv=true)
	{
		$qry = "SELECT vorname, nachname, titelpre, titelpost, kurzbz, vornamen, uid
			FROM campus.vw_mitarbeiter
			WHERE
				lower(nachname) like lower('%".$this->db_escape($filter)."%')
				OR lower(uid) like lower('%".$this->db_escape($filter)."%')
				OR lower(vorname) like lower('%".$this->db_escape($filter)."%')
				OR lower(vorname || ' ' || nachname) like lower('%".$this->db_escape($filter)."%')
				OR lower(nachname || ' ' || vorname) like lower('%".$this->db_escape($filter)."%')
			ORDER BY nachname, vorname";

		if(!is_null($limit) && is_numeric($limit))
			$qry.=" LIMIT ".$limit;

		//echo $qry;
		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new mitarbeiter();

				$obj->uid = $row->uid;
				$obj->vorname = $row->vorname;
				$obj->nachname = $row->nachname;
				$obj->titelpre = $row->titelpre;
				$obj->titelpost = $row->titelpost;
				$obj->kurzbz = $row->kurzbz;
				$obj->vornamen = $row->vornamen;

				$this->result[] = $obj;
			}

			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Liefert die Personen die den Suchkriterien entsprechen
	 *
	 * @param $filter
	 * @return boolean
	 */
	public function searchPersonal($filter)
	{
		$qry = "SELECT
					distinct on(mitarbeiter_uid) *, tbl_benutzer.aktiv as aktiv, tbl_mitarbeiter.insertamum,
					tbl_mitarbeiter.insertvon, tbl_mitarbeiter.updateamum, tbl_mitarbeiter.updatevon, tbl_person.svnr
				FROM ((public.tbl_mitarbeiter JOIN public.tbl_benutzer ON(mitarbeiter_uid=uid)) JOIN public.tbl_person USING(person_id))  LEFT JOIN campus.tbl_resturlaub USING(mitarbeiter_uid)
				WHERE lower(COALESCE(nachname,'') ||' '|| COALESCE(vorname,'')) ~* lower(".$this->db_add_param($filter).") OR
				      lower(COALESCE(vorname,'') ||' '|| COALESCE(nachname,'')) ~* lower(".$this->db_add_param($filter).") OR
				      uid ~* ".$this->db_add_param($filter)." ";
		if(is_numeric($filter))
			$qry.="OR personalnummer = ".$this->db_add_param($filter)." OR svnr = ".$this->db_add_param($filter).";";

		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new mitarbeiter();

				$obj->person_id = $row->person_id;
				$obj->staatsbuergerschaft = $row->staatsbuergerschaft;
				$obj->geburtsnation = $row->geburtsnation;
				$obj->sprache = $row->sprache;
				$obj->anrede = $row->anrede;
				$obj->titelpost = $row->titelpost;
				$obj->titelpre = $row->titelpre;
				$obj->nachname = $row->nachname;
				$obj->vorname = $row->vorname;
				$obj->vornamen = $row->vornamen;
				$obj->gebdatum = $row->gebdatum;
				$obj->gebort = $row->gebort;
				$obj->gebzeit = $row->gebzeit;
				$obj->anmerkung = $row->anmerkung;
				$obj->homepage = $row->homepage;
				$obj->svnr = $row->svnr;
				$obj->ersatzkennzeichen = $row->ersatzkennzeichen;
				$obj->familienstand = $row->familienstand;
				$obj->geschlecht = $row->geschlecht;
				$obj->anzahlkinder = $row->anzahlkinder;
				$obj->bnaktiv = $this->db_parse_bool($row->aktiv);
				$obj->uid = $row->uid;
				$obj->personalnummer = $row->personalnummer;
				$obj->telefonklappe = $row->telefonklappe;
				$obj->kurzbz = $row->kurzbz;
				$obj->lektor = $this->db_parse_bool($row->lektor);
				$obj->fixangestellt = $this->db_parse_bool($row->fixangestellt);
				$obj->bismelden = $this->db_parse_bool($row->bismelden);
				$obj->stundensatz = $row->stundensatz;
				$obj->ausbildungcode = $row->ausbildungcode;
				$obj->ort_kurzbz = $row->ort_kurzbz;
				$obj->standort_id = $row->standort_id;
				$obj->anmerkung = $row->anmerkung;
				$obj->alias = $row->alias;
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;

				$obj->urlaubstageprojahr = $row->urlaubstageprojahr;
				$obj->resturlaubstage = $row->resturlaubstage;

				$this->result[] = $obj;
			}
			return false;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * gibt array mit allen Mitarbeitern zurueck
	 * @return array mit Mitarbeitern
	 */
	public function getMitarbeiterOrganisationseinheit($oe_kurzbz)
	{
		$sql_query="SELECT DISTINCT campus.vw_mitarbeiter.uid, titelpre, titelpost, vorname, vornamen, nachname, gebdatum, gebort, gebzeit, anmerkung, aktiv,
					homepage, vw_mitarbeiter.updateamum, vw_mitarbeiter.updatevon, personalnummer, kurzbz, lektor, fixangestellt, standort_id, telefonklappe FROM campus.vw_mitarbeiter
					JOIN public.tbl_benutzerfunktion USING (uid)
					WHERE funktion_kurzbz='oezuordnung' AND oe_kurzbz=".$this->db_add_param($oe_kurzbz)." AND
					(tbl_benutzerfunktion.datum_von is null OR tbl_benutzerfunktion.datum_von<=now()) AND
					(tbl_benutzerfunktion.datum_bis is null OR tbl_benutzerfunktion.datum_bis>=now())
					ORDER BY nachname, vorname";

		if($this->db_query($sql_query))
		{
			$num_rows=$this->db_num_rows();
			$result=array();
			for($i=0;$i<$num_rows;$i++)
			{
	   			$row=$this->db_fetch_object(null,$i);
				$l=new mitarbeiter();
				// Personendaten
				$l->uid=$row->uid;
				$l->titelpre=$row->titelpre;
				$l->titelpost=$row->titelpost;
				$l->vorname=$row->vorname;
				$l->vornamen=$row->vornamen;
				$l->nachname=$row->nachname;
				$l->gebdatum=$row->gebdatum;
				$l->gebort=$row->gebort;
				$l->gebzeit=$row->gebzeit;
				//$l->foto=$row->foto;
				$l->anmerkung=$row->anmerkung;
				$l->aktiv= $this->db_parse_bool($row->aktiv);
				$l->homepage=$row->homepage;
				$l->updateamum=$row->updateamum;
				$l->updatevon=$row->updatevon;
				// Lektorendaten
				$l->personalnummer=$row->personalnummer;
				$l->kurzbz=$row->kurzbz;
				$l->lektor= $this->db_parse_bool($row->lektor);
				$l->fixangestellt=$this->db_parse_bool($row->fixangestellt);
				$l->standort_id = $row->standort_id;
				$l->telefonklappe=$row->telefonklappe;

				// Lektor in Array speichern
				$result[]=$l;
			}
			return $result;
		}
		else
		{
			$this->errormsg="Fehler beim Laden der Daten";
			return false;
		}
	}

	/**
	 * Gibt ein Array mit den UIDs der Vorgesetzten zurück
	 * @return uid
	 */
	public function getVorgesetzte($uid=null)
	{
		$return=false;
		if (is_null($uid))
			$uid=$this->uid;

		$qry = "SELECT
					uid  as vorgesetzter
				FROM
					public.tbl_benutzerfunktion
				WHERE
					funktion_kurzbz='Leitung' AND
					(datum_von is null OR datum_von<=now()) AND
					(datum_bis is null OR datum_bis>=now()) AND
					oe_kurzbz in (SELECT oe_kurzbz
								  FROM public.tbl_benutzerfunktion
								  WHERE
									funktion_kurzbz='oezuordnung' AND uid=".$this->db_add_param($uid)." AND
									(datum_von is null OR datum_von<=now()) AND
									(datum_bis is null OR datum_bis>=now())
								  );";


		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				if ($row->vorgesetzter!='')
				{
					$this->vorgesetzte[]=$row->vorgesetzter;
					$return=true;
				}
			}

			$this->vorgesetzte = array_unique($this->vorgesetzte);
		}
		else
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage!';
		}
		return $return;
	}

	/**
	 * Gibt ein Array mit den UIDs der Untergebenen zurueck
	 */
	public function getUntergebene($uid=null)
	{
		if (is_null($uid))
			$uid=$this->uid;

		// Organisationseinheiten holen von denen die Person die Leitung hat
		$qry = "SELECT * FROM public.tbl_benutzerfunktion
				WHERE funktion_kurzbz='Leitung' AND uid=".$this->db_add_param($uid)." AND
				(tbl_benutzerfunktion.datum_von is null OR tbl_benutzerfunktion.datum_von<=now()) AND
				(tbl_benutzerfunktion.datum_bis is null OR tbl_benutzerfunktion.datum_bis>=now())";

		if($this->db_query($qry))
		{
			$oe='';
			while($row = $this->db_fetch_object())
			{
				if($oe!='')
					$oe.=',';
				$oe.=$this->db_add_param($row->oe_kurzbz);
			}
		}

		//Alle Personen holen die dieser Organisationseinheit untergeordnet sind
		$qry = "SELECT distinct uid FROM public.tbl_benutzerfunktion WHERE ((funktion_kurzbz='oezuordnung' AND (false ";

		if($oe!='')
			$qry.=" OR oe_kurzbz in($oe)";

		$qry.=")) ";

		if($oe!='')
			$qry.=" OR (funktion_kurzbz='ass' AND oe_kurzbz in($oe))";

		$qry.= ") AND (tbl_benutzerfunktion.datum_von is null OR tbl_benutzerfunktion.datum_von<=now()) AND
					 (tbl_benutzerfunktion.datum_bis is null OR tbl_benutzerfunktion.datum_bis>=now());";

		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$this->untergebene[]=$row->uid;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Ermitteln der Untergebenen';
			return false;
		}

	}

    /**
     * Liefert MitarbeiterInnen für die Verwaltung der Zutrittskarten
     *
     * @param $filter Buchstabe mit dem der Mitarbeiter beginnt
     * @param $fixangestellt false wenn externer mitarbeiter
     * @param $studSemArray Array mit Studiensemestern in denen der externe Lektor zumindest in einem Unterrichtet haben soll oder NULL. Wenn NULL werden nur externe Mitarbeiter ohne Lehrauftrag ausgegeben.
     * @return boolean
     */
    public function getMitarbeiterForZutrittskarte($filter, $fixangestellt=true, $studSemArray)
    {
        $qry = "SELECT
                    vorname,nachname,gebdatum,uid,personalnummer,person_id
                FROM
                    campus.vw_mitarbeiter
                WHERE
                	aktiv='true'";
        if($filter!='')
			$qry.=" AND UPPER(SUBSTRING(nachname,1,1))=".$this->db_add_param($filter,FHC_STRING);

        if($fixangestellt)
            $qry.=" AND fixangestellt";
        else
        {
            if ($studSemArray==null)
            	$qry.=" AND NOT fixangestellt AND personalnummer>0 AND NOT EXISTS
	                (SELECT * FROM lehre.tbl_lehreinheitmitarbeiter
	                JOIN lehre.tbl_lehreinheit USING(lehreinheit_id)
	                WHERE tbl_lehreinheitmitarbeiter.mitarbeiter_uid=uid) ";
            else
	        	$qry.=" AND NOT fixangestellt AND EXISTS
	                (SELECT * FROM lehre.tbl_lehreinheitmitarbeiter
	                JOIN lehre.tbl_lehreinheit USING(lehreinheit_id)
	                WHERE tbl_lehreinheitmitarbeiter.mitarbeiter_uid=uid
	                AND tbl_lehreinheit.studiensemester_kurzbz in (".$this->implode4SQL($studSemArray).")) ";
        }
        $qry.=" ORDER BY nachname, vorname";
        if($result = $this->db_query($qry))
        {
            while($row = $this->db_fetch_object($result))
            {
                $mi = new mitarbeiter();

                $mi->vorname = $row->vorname;
                $mi->nachname = $row->nachname;
                $mi->gebdatum = $row->gebdatum;
                $mi->uid = $row->uid;
                $mi->personalnummer = $row->personalnummer;
                $mi->person_id = $row->person_id;

                $this->result[] = $mi;
            }
            return true;
        }
        else
        {
            $this->errormsg = "Fehler bei der Abfrage aufgetreten";
            return false;
        }
    }


    /**
     * Gibt alle Mitarbeiter während des Meldezeitraumes zurück
     * @param type $meldungBeginn Start des Meldezeitraumes
     * @param type $meldungEnde Ende des Meldezeitraumes
     * @return boolean
     */
    public function getMitarbeiterforMeldung($meldungBeginn, $meldungEnde)
    {

	$qry='SELECT DISTINCT ON (UID) *
		FROM
			public.tbl_mitarbeiter
			JOIN public.tbl_benutzer ON(mitarbeiter_uid=uid)
			JOIN public.tbl_person USING(person_id)
			JOIN bis.tbl_bisverwendung USING(mitarbeiter_uid)
			JOIN bis.tbl_beschaeftigungsausmass USING(beschausmasscode)
		WHERE
			bismelden
			AND personalnummer>0
			AND (tbl_bisverwendung.ende is NULL OR tbl_bisverwendung.ende>'.$this->db_add_param($meldungBeginn).')
		ORDER BY uid, nachname,vorname
		';

	if($result = $this->db_query($qry))
	{
	    while($row = $this->db_fetch_object($result))
	    {
		$l=new mitarbeiter();
		// Personendaten
		$l->uid=$row->uid;
		$l->titelpre=$row->titelpre;
		$l->titelpost=$row->titelpost;
		$l->vorname=$row->vorname;
		$l->vornamen=$row->vornamen;
		$l->nachname=$row->nachname;
		$l->gebdatum=$row->gebdatum;
		$l->gebort=$row->gebort;
		$l->gebzeit=$row->gebzeit;
		$l->geschlecht=$row->geschlecht;
		//$l->foto=$row->foto;
		$l->anmerkung=$row->anmerkung;
		$l->aktiv= $this->db_parse_bool($row->aktiv);
		$l->homepage=$row->homepage;
		$l->updateamum=$row->updateamum;
		$l->updatevon=$row->updatevon;
		// Lektorendaten
		$l->personalnummer=$row->personalnummer;
		$l->kurzbz=$row->kurzbz;
		$l->lektor= $this->db_parse_bool($row->lektor);
		$l->fixangestellt= $this->db_parse_bool($row->fixangestellt);
		$l->standort_id = $row->standort_id;
		$l->telefonklappe=$row->telefonklappe;
		$l->ausbildungcode=$row->ausbildungcode;
		$l->ba1code=$row->ba1code;
		$l->ba2code=$row->ba2code;
		$l->verwendung_code=$row->verwendung_code;
		$l->beschäftigungsausmass = $row->max;
		$l->vertragsstunden = $row->vertragsstunden;

		// Lektor in Array speichern
		array_push($this->result, $l);
	    }
	    return true;
	}
	return false;
    }

	/**
	 * Laedt einen Mitarbeiter anhand der Personalnummer
	 *
	 * @param $uid
	 * @return true wenn ok, sonst false
	 */
	public function getMitarbeiterFromPersonalnummer($personalnummer)
	{
		$qry = "SELECT * FROM public.tbl_mitarbeiter
				WHERE personalnummer=".$this->db_add_param($personalnummer).";";

		if($this->db_query($qry))
		{
			if($row = $this->db_fetch_object())
			{
				if(!benutzer::load($row->mitarbeiter_uid))
					return false;

				$this->personalnummer = $row->personalnummer;
				$this->kurzbz = $row->kurzbz;
				$this->lektor = $this->db_parse_bool($row->lektor);
				$this->fixangestellt = $this->db_parse_bool($row->fixangestellt);
				$this->standort_id = $row->standort_id;
				$this->telefonklappe = $row->telefonklappe;
				$this->ort_kurzbz = $row->ort_kurzbz;
				$this->stundensatz = $row->stundensatz;
				$this->anmerkung = $row->anmerkung;
				$this->ext_id_mitarbeiter = $row->ext_id;
				$this->bismelden = $this->db_parse_bool($row->bismelden);
				$this->kleriker = $this->db_parse_bool($row->kleriker);

				return true;
			}
			else
			{
				$this->errormsg = "Kein Eintrag gefunden fuer den Benutzer\n";
				return false;
			}
		}
		else
		{
			$this->errormsg = "Fehler beim Laden der Daten\n";
			return false;
		}
	}
}
?>
