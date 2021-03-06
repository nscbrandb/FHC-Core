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
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at> and
 */
require_once(dirname(__FILE__).'/vorlage.class.php');
require_once(dirname(__FILE__).'/addon.class.php');
require_once(dirname(__FILE__).'/studiengang.class.php');

class dokument_export
{
	private $content_xsl; // XSL Vorlage fuer content.xml
	private $styles_xsl; // XSL Vorlage fuer styles.xml
	private $xml_data; // XML Daten
	private $vorlage; // Vorlage Objekt
	private $vorlage_file; // Vorlage ODT/ODS in das hineingezippt wird
	private $outputformat; // Datentyp des Ausgabefiles
	private $filename; // Dateiname des Ausgabefiles
	private $temp_filename;
	private $temp_folder;
	private $images=array();

	/**
	 * Konstruktor
	 */
	public function __construct($vorlage, $oe_kurzbz=0, $version=null)
	{
		//Vorlage aus der Datenbank holen
		$this->vorlage = new vorlage();
		if(!$this->vorlage->getAktuelleVorlage($oe_kurzbz, $vorlage, $version))
			die('Keine Dokumentenvorlage gefunden');

		$this->content_xsl = new DOMDocument;
		if(!$this->content_xsl->loadXML($this->vorlage->text))
			die('unable to load xsl');

		// Style Vorlage laden falls vorhanden
		if($this->vorlage->style!='')
		{
			$this->styles_xsl = new DOMDocument;
			if(!$this->styles_xsl->loadXML($this->vorlage->style))
				die('unable to load styles xsl');
		}

		switch($this->vorlage->mimetype)
		{
			case 'application/vnd.oasis.opendocument.text':
					$this->outputformat = 'odt';
					$this->vorlage_file = $this->vorlage->vorlage_kurzbz.'.odt';
					break;
			case 'application/vnd.oasis.opendocument.spreadsheet':
					$this->outputformat = 'ods';
					$this->vorlage_file = $this->vorlage->vorlage_kurzbz.'.ods';
					break;
			default:
					$this->outputformat = 'pdf';
					$this->vorlage_file = $this->vorlage->vorlage_kurzbz.'.odt';
		}

		if($this->vorlage->bezeichnung!='')
			$this->filename = $this->vorlage->bezeichnung;
		else
			$this->filename = $this->vorlage->vorlage_kurzbz;

	}

	/**
	 * Laedt die XML Daten fuer die XSL Transformation anhand eines Arrays
	 * @param $data Array mit Daten
	 * @param $root Bezeichnung des Root Nodes
	 * @return boolean true
	 */
	public function addDataArray($data, $root)
	{
		$this->xml_data = new DOMDocument;
		$this->xml_data->loadXML($this->ConvertArrayToXML($data,$root));
		return true;
	}

	/**
	 * XML Daten fuer die XSL Transformation
	 * @param $xml
	 * @return boolean true
	 */
	public function addDataXML($xml)
	{
		$this->xml_data = new DOMDocument;
		$this->xml_data->loadXML($xml);
		return true;
	}

	/**
	 * URL zu XML Datei die fuer XSLTransformation verwendet werden soll
	 * @param $xml URL zu XML
	 * @param $params GET Parameter die an XML URL uebergeben werden
	 * @return boolean true
	 */
	public function addDataURL($xml, $params)
	{
		$xml_found = false;
		$addons = new addon();

		foreach($addons->aktive_addons as $addon)
		{
			$xmlfile = DOC_ROOT.'addons/'.$addon.'/rdf/'.$xml;
			if(file_exists($xmlfile))
			{
				$xml_found = true;
				$xml_url = XML_ROOT.'../addons/'.$addon.'/rdf/'.$xml.'?'.$params;
				break;
			}
		}
		if(!$xml_found)
			$xml_url=XML_ROOT.$xml.'?'.$params;


		// Load the XML source
		$this->xml_data = new DOMDocument;

		if(!$this->xml_data->load($xml_url))
			die('unable to load xml: '.$xml_url);

		return true;
	}

	/**
	 * Fuegt ein Bild zum Dokument hinzu
	 * @param $path Pfad zum Bild im Filesystem
	 * @param $name Name des Bildes das es im Dokument haben soll ohne Pfad (zB 1.png)
	 * @param $contenttype Contenttype des Bilds (zB image/png)
	 */
	public function addImage($path, $name, $contenttype)
	{
		$this->images[]=array('path'=>$path,'name'=>$name,'contenttype'=>$contenttype);
	}

	/**
	 * Erstellt das ODT Dokument inklusive Bilder und konvertiert es ins gewuenschte Format
	 * @param $outputformat ODT, PDF, DOC
	 * @return true wenn ok
	 */
	public function create($outputformat=null)
	{
		if(!is_null($outputformat))
			$this->outputformat=$outputformat;

		// content.xml erstellen
		$proc = new XSLTProcessor;
		$proc->importStyleSheet($this->content_xsl);

		$contentbuffer = $proc->transformToXml($this->xml_data);

		$this->temp_folder = '/tmp/fhcunoconv-'.uniqid();
		mkdir($this->temp_folder);
		chdir($this->temp_folder);
		file_put_contents('content.xml', $contentbuffer);

		// styles.xml erstellen
		if(!is_null($this->styles_xsl))
		{
			$style_proc = new XSLTProcessor;
			$style_proc->importStyleSheet($this->styles_xsl);

			$stylesbuffer = $style_proc->transformToXml($this->xml_data);

			file_put_contents('styles.xml', $stylesbuffer);
		}

		// Template holen
		$vorlage_found=false;
		$addons = new addon();

		foreach($addons->aktive_addons as $addon)
		{
			$zipfile = DOC_ROOT.'addons/'.$addon.'/system/vorlage_zip/'.$this->vorlage_file;

			if(file_exists($zipfile))
			{
				$vorlage_found=true;
				break;
			}
		}
		if(!$vorlage_found)
			$zipfile = DOC_ROOT.'system/vorlage_zip/'.$this->vorlage_file;

		$tempname_zip = 'out.zip';
		if(!copy($zipfile, $tempname_zip))
			die('copy failed');

		exec("zip $tempname_zip content.xml");
		if(!is_null($this->styles_xsl))
			exec("zip $tempname_zip styles.xml");

		// bilder hinzufuegen
		if(count($this->images)>0)
		{
			// Unterordner fuer die Bilder erstellen
			mkdir('Pictures');

			// Manifest Datei holen
			exec('unzip '.$tempname_zip.' META-INF/manifest.xml');

			// Bild zur Manifest Datei hinzufuegen
			$manifest = file_get_contents('META-INF/manifest.xml');

			$manifest_xml = new DOMDocument;
			if(!$manifest_xml->loadXML($manifest))
				die('Manifest File ungueltig');

			//root-node holen
			$root = $manifest_xml->getElementsByTagName('manifest')->item(0);

			foreach($this->images as $bild)
			{
				copy($bild['path'], 'Pictures/'.$bild['name']);

				//Neues Element unterhalb des Root Nodes anlegen
				$node = $manifest_xml->createElement("manifest:file-entry");
				$node->setAttribute("manifest:full-path",'Pictures/'.$bild['name']);
				$node->setAttribute("manifest:media-type",$bild['contenttype']);
				$root->appendChild($node);
			}

			$out = $manifest_xml->saveXML();

			//geaenderte Manifest Datei speichern und wieder ins Zip packen
			file_put_contents('META-INF/manifest.xml', $out);
			exec('zip '.$tempname_zip.' META-INF/*');

			// Bilder zum ZIP-File hinzufuegen
			exec("zip $tempname_zip Pictures/*");
		}

		clearstatcache();

		switch($this->outputformat)
		{
			case 'pdf':
				$this->temp_filename='out.pdf';
				exec("unoconv -e IsSkipEmptyPages=false --stdout -f pdf $tempname_zip > ".$this->temp_filename, $out, $ret);
				if($ret!=0)
				{
					$this->errormsg = 'Dokumentenkonvertierung ist derzeit nicht möglich. Bitte informieren Sie den Administrator';
					return false;
				}
				break;
			case 'doc':
				$this->temp_filename='out.doc';
				exec("unoconv -e IsSkipEmptyPages=false --stdout -f doc $tempname_zip > ".$this->temp_filename, $out, $ret);
				if($ret!=0)
				{
					$this->errormsg = 'Dokumentenkonvertierung ist derzeit nicht möglich. Bitte informieren Sie den Administrator';
					return false;
				}
				break;
			case 'odt':
			default:
				$this->temp_filename = $tempname_zip;

		}

		return true;
	}

	/**
	 * Liefert das Dokument mit den passenden Headern zum Download oder als ReturnValue
	 * @param $download wenn true werden Header gesendet und das Dokument ausgeliefert
	 * 					wenn false wird es als Returnwert zurueckgeliefert
	 * @return boolean true oder Dokument
	 */
	public function output($download=true)
	{

		$fsize = filesize($this->temp_filename);
		if(!$handle = fopen($this->temp_filename,'r'))
			die('load failed');

		if($download)
		{
			switch($this->outputformat)
			{
				case 'pdf':
		            header('Content-type: application/pdf');
		            header('Content-Disposition: attachment; filename="'.$this->filename.'.pdf"');
		            header('Content-Length: '.$fsize);
					break;

				case 'doc':
		            header('Content-type: application/vnd.ms-word');
		            header('Content-Disposition: attachment; filename="'.$this->filename.'.doc"');
		            header('Content-Length: '.$fsize);
					break;

				case 'odt':
		            header('Content-type: application/vnd.oasis.opendocument.text');
		            header('Content-Disposition: attachment; filename="'.$this->filename.'.odt"');
		            header('Content-Length: '.$fsize);
					break;
			}

			while (!feof($handle))
			{
				echo fread($handle, 8192);
			}
			fclose($handle);
			return true;
		}
		else
		{
			$data = fread($handle, filesize($file));
			fclose($handle);
			return $data;
		}
	}

	/**
	 * Loescht die Temporaeren Dateien die angelegt wurden
	 * @return boolean true
	 */
	public function close()
	{
		unlink('content.xml');
		if($this->styles_xsl!='')
			unlink('styles.xml');

		unlink('out.zip');
		unlink($this->temp_filename);

		if(count($this->images)>0)
		{
			unlink('META-INF/manifest.xml');

			foreach($this->images as $bild)
				unlink('Pictures/'.$bild['name']);
			rmdir('Pictures');
			rmdir('META-INF');
		}


		rmdir($this->temp_folder);

		return true;
	}

	/**
	 * Konvertiert das Array in ein XML
	 * @param $data PHP Array mit den Daten
	 * @param $rootElement Bezeichnung des XML Wurzelelements
	 * @param $xml_data SimpleXMLElement fuer Rekursionsaufloesung
	 * @return xml
	 */
	private function ConvertArrayToXML($data, $rootElement=null, $xml_data=null )
	{
		$_xml_data = $xml_data;
		if ($_xml_data === null)
			$_xml_data = new SimpleXMLElement($rootElement !== null ? '<'.$rootElement.' />' : '<root/>');

		foreach( $data as $key => $value )
		{
		    if( is_array($value) )
			{
		        if( is_numeric($key) )
				{
		            $key = 'item'.$key; //dealing with <0/>..<n/> issues
			        $this->ConvertArrayToXML($value, null, $_xml_data);
		        }
				else
				{
		        	$subnode = $_xml_data->addChild($key);
			        $this->ConvertArrayToXML($value, null, $subnode);
				}
		    }
			else
		        $_xml_data->addChild("$key",htmlspecialchars("$value"));
		}
		return $_xml_data->asXML();
	}
}
?>
