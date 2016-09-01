<?php
/* Copyright (C) 2016 fhcomplete.org
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
 * Authors: Andreas Moik <moik@technikum-wien.at>.
 */

class Pdf
{
	public $errormsg = "";

	/**
	* Fügt beliebig viele PDF Dateien zu einer zusammen
	* @param array $files Array mit Dateien
	* @param string $outFile Zieldatei
	* @return boolean
	*/
	public function merge($files, $outFile)
	{
		$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outFile ";
		$finfo = finfo_open(FILEINFO_MIME_TYPE);

		// add all pdf files to the command
		foreach($files as $f)
		{
			$cmd .= $f." ";
			if(!file_exists($f))
			{
				$this->errormsg = "File not found: '$f'";
				return false;
			}
			if(finfo_file($finfo, $f) != "application/pdf")
			{
				$this->errormsg = "Wrong format(".finfo_file($finfo, $f)."): '$f'";
				return false;
			}
		}

		finfo_close($finfo);

		exec($cmd, $out, $ret);
		if($ret!=0)
		{
			$this->errormsg = 'PDF-zusammenfuegung ist derzeit nicht möglich. Bitte informieren Sie den Administrator';
			return false;
		}
		return true;
	}


	/**
	* Konvertiert eine jpeg Datei zu einer PDF
	* @param string $image jpeg Datei
	* @param string $outFile Zieldatei
	* @return boolean
	*/
	public function jpegToPdf($image, $outFile)
	{
		if(!file_exists($image))
		{
			$this->errormsg = "File not found: '$image'";
			return false;
		}

		$s = getimagesize($image);

		/*
		 * längere Seite ermitteln
		 * Hochformat wenn die Seiten gleich lang sind.
		 */
		if($s[0] > $s[1])
		{
			$height = 595;
			$width = 842;
		}
		else
		{
			$height = 842;
			$width = 595;
		}

		// -r300 = 300 ppi
		$cmd = 'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -r100 -o '.$outFile.' viewjpeg.ps -c "('.$image.') << /PageSize [' . $width . ' ' . $height .'] /.HWMargins [18 18 18 13.5] /countspaces {  [ exch { dup 32 ne { pop } if  } forall ] length } bind def  >>  setpagedevice viewJPEG"';

		exec($cmd, $out, $ret);
		if($ret!=0)
		{
			$this->errormsg = 'jpegToPdf ist derzeit nicht möglich. Bitte informieren Sie den Administrator';
			return false;
		}
		return true;
	}
}

