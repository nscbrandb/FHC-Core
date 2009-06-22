<?php
/* Copyright (C) 2009 Technikum-Wien
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
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 *          Rudolf Hangl <rudolf.hangl@technikum-wien.at> and
 *			Gerald Simane-Sequens <gerald.simane-sequens@technikum-wien.at>
 */
require_once('../config/vilesci.config.inc.php');
require_once('../include/functions.inc.php');
require_once('../include/variable.class.php');

$variable = new variable();
$variable->loadVariables(get_uid());

?>

var currentAuswahl=new auswahlValues();
var LvTreeDatasource;
var LektorTreeDatasource;
var LektorTreeOpenStudiengang;
var StudentTreeDatasource;
var InteressentTreeDatasource;

// ****
// * initialisiert den Lektor Tree
// ****
function initLektorTree()
{
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	try
	{
		url = '<?php echo APP_ROOT; ?>rdf/mitarbeiter.rdf.php?user=true&'+gettimestamp(); //&lektor=true
		var LektorTree=document.getElementById('tree-lektor');

		if(LektorTree)
		{
			try
			{
				LektorTreeDatasource.removeXMLSinkObserver(LektorTreeSinkObserver);
				LektorTree.builder.removeListener(LektorTreeListener);
			}
			catch(e)
			{}
			
			//Alte DS entfernen
			var oldDatasources = LektorTree.database.GetDataSources();
			while(oldDatasources.hasMoreElements())
			{
				LektorTree.database.RemoveDataSource(oldDatasources.getNext());
			}

			var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
			LektorTreeDatasource = rdfService.GetDataSource(url);
			LektorTreeDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
			LektorTreeDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
			LektorTree.database.AddDataSource(LektorTreeDatasource);
			//LektorTreeDatasource.addXMLSinkObserver(LektorTreeSinkObserver);
			LektorTree.builder.addListener(LektorTreeListener);
		}
	}
	catch(e)
	{
		debug(e);
	}
}

// ****
// * Nach dem Rebuild wird die Lehreinheit wieder
// * markiert
// ****
var LektorTreeListener =
{
  willRebuild : function(builder) {  },
  didRebuild : function(builder)
  {
  	  //timeout nur bei Mozilla notwendig da sonst die rows
  	  //noch keine values haben. Ab Seamonkey funktionierts auch
  	  //ohne dem setTimeout
      window.setTimeout(LektorTreeSelectMitarbeiter,10);
  }
};

function LektorTreeSelectMitarbeiter()
{
	var tree=document.getElementById('tree-lektor');
	var items = tree.view.rowCount; //Anzahl der Zeilen ermitteln
	if(LektorTreeOpenStudiengang!=null)
	{
	   	for(var i=0;i<items;i++)
	   	{
	   		//Lehreinheit_id der row holen
			col = tree.columns ? tree.columns["studiengang_kz"] : "studiengang_kz";
			var studiengang_kz=tree.view.getCellText(i,col);
			if(studiengang_kz == LektorTreeOpenStudiengang)
			{
				tree.view.toggleOpenState(i);
				break;
			}
	   	}
	   	//nach dem laden der daten wieder ganz oben im tree positionieren da es sonst vorkommt, dass
	   	//der scrollbalken unterhalb aller eintraege rutscht und dann nichts mehr im tree sichtbar ist.
	   	//(funktioniert anscheinend auch nur mit setTimeout)
	   	window.setTimeout("document.getElementById('tree-lektor').treeBoxObject.scrollToRow(0)",10);
	}
}

// ****
// * Refresht den Lektor Tree
// ****
function RefreshLektorTree()
{
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	LektorTreeDatasource.Refresh(true);
	document.getElementById('tree-lektor').builder.rebuild();
}

// ****
// * Loescht die Lkt Funktion eines Lektors
// ****
function LektorFunktionDel()
{
	tree = document.getElementById('tree-lektor');

	//Nachsehen ob Mitarbeiter markiert wurde
	var idx;
	if(tree.currentIndex>=0)
		idx = tree.currentIndex;
	else
	{
		alert('Bitte zuerst einen Mitarbeiter markieren');
		return false;
	}

	try
	{
		//UID holen
		var col = tree.columns ? tree.columns["uid"] : "uid";
		var uid=tree.view.getCellText(idx,col);
		//Stg_kz holen
		var stg_idx = tree.view.getParentIndex(idx);
		var col = tree.columns ? tree.columns["studiengang_kz"] : "studiengang_kz";
		var studiengang_kz=tree.view.getCellText(stg_idx,col);
	}
	catch(e)
	{
		alert(e);
		return false;
	}

	//Request absetzen
	var req = new phpRequest('tempusDBDML.php','','');

	req.add('type', 'delFunktionFromMitarbeiter');
	req.add('studiengang_kz', studiengang_kz);
	req.add('uid', uid);

	var response = req.executePOST();
	//Returnwert auswerten
	var val =  new ParseReturnValue(response)

	if (!val.dbdml_return)
	{
		alert(val.dbdml_errormsg)
	}
	else
	{
		//Refresh des Trees
		LektorTreeOpenStudiengang = studiengang_kz;
		RefreshLektorTree();
	}
}

// ****
// * Sendet ein Mail an die Mitarbeiter die im Lektor Tree
// * markiert wurden.
// ****
function LektorFunktionMail()
{
	mailempfaenger='';
	var tree=document.getElementById('tree-lektor');
	var numRanges = tree.view.selection.getRangeCount();
	var start = new Object();
	var end = new Object();
	var anzfault=0;
	//Markierte Datensaetze holen
	for (var t=0; t<numRanges; t++)
	{
  		tree.view.selection.getRangeAt(t,start,end);
  		for (v=start.value; v<=end.value; v++)
  		{
  			var col = tree.columns ? tree.columns["uid"] : "uid";
  			if(tree.view.getCellText(v,col).length>1)
  			{
  				if(mailempfaenger!='')
					mailempfaenger=mailempfaenger+'<?php echo $variable->variable->emailadressentrennzeichen; ?>'+tree.view.getCellText(v,col)+'@<?php echo DOMAIN; ?>';
				else
					mailempfaenger='mailto:'+tree.view.getCellText(v,col)+'@<?php echo DOMAIN; ?>';
  			}
  			else
  			{
  				anzfault=anzfault+1;
  			}
  		}
	}
	if(anzfault!=0)
		alert(anzfault+' Mitarbeiter konnten nicht hinzugefuegt werden weil keine UID eingetragen ist!');
	if(mailempfaenger!='')
		window.location.href=mailempfaenger;
}

function auswahlValues()
{
	this.stg_kz=null;
	this.sem=null;
	this.ver=null;
	this.grp=null;
	this.gruppe=null;
	this.lektor_uid=null;
}

// ---------------------------------------------------------
// -------------- onVerbandSelect --------------------------

function onVerbandSelect(event)
{
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");

	var contentFrame=document.getElementById('iframeTimeTableWeek');
	var tree=document.getElementById('tree-verband');

	//Wenn nichts markiert wurde -> beenden
	if(tree.currentIndex==-1)
		return;

	var row = { };
    var col = { };
    var child = { };

    tree.treeBoxObject.getCellAt(event.pageX, event.pageY, row, col, child)

    //Wenn es keine Row ist sondern ein Header oder Scrollbar dann abbrechen
    if (!col.value)
       	return false;

    //Wenn eine andere row markiert ist als angeklickt wurde -> beenden.
	//Dies kommt vor wenn ein Subtree geoeffnet wird
	if(row.value!=tree.currentIndex)
		return;

	//Export aktivieren
	document.getElementById('student-toolbar-export').disabled=false;

    // Progressmeter starten. Ab jetzt keine 'return's mehr.
    document.getElementById('statusbar-progressmeter').setAttribute('mode','undetermined');
    //globalProgressmeter.StartPM();

	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var stg_kz=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["sem"] : "sem";
	var sem=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["ver"] : "ver";
	var ver=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["grp"] : "grp";
	var grp=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["gruppe"] : "gruppe";
	var gruppe=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["typ"] : "typ";
	var typ=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["stsem"] : "stsem";
	var stsem=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["tree-verband-col-orgform"] : "tree-verband-col-orgform";
	var orgform=tree.view.getCellText(tree.currentIndex,col);

	currentAuswahl.stg_kz=stg_kz;
	currentAuswahl.sem=sem;
	currentAuswahl.ver=ver;
	currentAuswahl.grp=grp;
	currentAuswahl.gruppe=gruppe;

	if(typ=='')
	{
		//Bei Ansicht von Ab-/Unterbrecher den Button "->Student" anzeigen
		if(sem=='0')
			document.getElementById('student-toolbar-student').hidden=false;
		else
			document.getElementById('student-toolbar-student').hidden=true;

		//Wenn der Interessenten Tab markiert ist, dann den Studenten Tab markieren
		if(document.getElementById('main-content-tabs').selectedItem==document.getElementById('tab-interessenten'))
			document.getElementById('main-content-tabs').selectedItem=document.getElementById('tab-studenten');

		// -------------- Studenten --------------------------
		try
		{
			stsem = getStudiensemester();
			url = "<?php echo APP_ROOT; ?>rdf/student.rdf.php?studiengang_kz="+stg_kz+"&semester="+sem+"&verband="+ver+"&gruppe="+grp+"&gruppe_kurzbz="+gruppe+"&studiensemester_kurzbz="+stsem+"&typ=student&orgform="+orgform+"&"+gettimestamp();
			var treeStudent=document.getElementById('student-tree');

			//Alte DS entfernen
			var oldDatasources = treeStudent.database.GetDataSources();
			while(oldDatasources.hasMoreElements())
			{
				treeStudent.database.RemoveDataSource(oldDatasources.getNext());
			}

			try
			{
				StudentTreeDatasource.removeXMLSinkObserver(StudentTreeSinkObserver);
				treeStudent.builder.removeListener(StudentTreeListener);
			}
			catch(e)
			{}
			var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
			StudentTreeDatasource = rdfService.GetDataSource(url);
			StudentTreeDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
			StudentTreeDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
			treeStudent.database.AddDataSource(StudentTreeDatasource);
			StudentTreeDatasource.addXMLSinkObserver(StudentTreeSinkObserver);
			treeStudent.builder.addListener(StudentTreeListener);

			//Detailfelder Deaktivieren
			StudentDetailReset();
			StudentDetailDisableFields(true);
			StudentPrestudentDisableFields(true);
			StudentKontoDisableFields(true);
			StudentAkteDisableFields(true);
			StudentIODisableFields(true);
			StudentNoteDisableFields(true);
			document.getElementById('student-kontakt').setAttribute('src','');
			document.getElementById('student-betriebsmittel').setAttribute('src','');
			StudentAbschlusspruefungDisableFields(true);
		}
		catch(e)
		{
			debug(e);
		}

		// -------------- Lehrveranstaltung --------------------------
		try
		{
			url = '<?php echo APP_ROOT; ?>rdf/lehrveranstaltung_einheiten.rdf.php?stg_kz='+stg_kz+'&sem='+sem+'&ver='+ver+'&grp='+grp+'&gruppe='+gruppe+'&orgform='+orgform+"&"+gettimestamp();
			var treeLV=document.getElementById('lehrveranstaltung-tree');

			try
			{
				LvTreeDatasource.removeXMLSinkObserver(LvTreeSinkObserver);
				treeLV.builder.removeListener(LvTreeListener);
			}
			catch(e)
			{}
			
			//Alte DS entfernen
			var oldDatasources = treeLV.database.GetDataSources();
			while(oldDatasources.hasMoreElements())
			{
				treeLV.database.RemoveDataSource(oldDatasources.getNext());
			}

			var rdfService1 = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);

			LvTreeDatasource = rdfService1.GetDataSource(url);
			LvTreeDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
			LvTreeDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
			treeLV.database.AddDataSource(LvTreeDatasource);
			LvTreeDatasource.addXMLSinkObserver(LvTreeSinkObserver);
			treeLV.builder.addListener(LvTreeListener);
			document.getElementById('lehrveranstaltung-toolbar-lehrauftrag').hidden=true;
		}
		catch(e)
		{
			debug(e);
		}
	}

	// Interessenten / Bewerber
	if(typ!='')
	{
		// Interessenten Tab markieren
		//document.getElementById('main-content-tabs').selectedItem=document.getElementById('tab-interessenten');

		// -------------- Interessenten / Bewerber --------------------------
		try
		{
			if(stsem=='' && typ=='')
				stsem='aktuelles';
			url = "<?php echo APP_ROOT; ?>rdf/student.rdf.php?"+"studiengang_kz="+stg_kz+"&semester="+sem+"&typ="+typ+"&studiensemester_kurzbz="+stsem+"&orgform="+orgform+"&"+gettimestamp();
			var treeInt=document.getElementById('student-tree');

			try
			{
				StudentTreeDatasource.removeXMLSinkObserver(StudentTreeSinkObserver);
				treeInt.builder.removeListener(StudentTreeListener);
			}
			catch(e)
			{}
			
			//Alte DS entfernen
			var oldDatasources = treeInt.database.GetDataSources();
			while(oldDatasources.hasMoreElements())
			{
				treeInt.database.RemoveDataSource(oldDatasources.getNext());
			}

			var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
			StudentTreeDatasource = rdfService.GetDataSource(url);
			StudentTreeDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
			StudentTreeDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
			treeInt.database.AddDataSource(StudentTreeDatasource);
			StudentTreeDatasource.addXMLSinkObserver(StudentTreeSinkObserver);
			treeInt.builder.addListener(StudentTreeListener);

			//Detailfelder Deaktivieren
			StudentDetailReset();
			StudentDetailDisableFields(true);
			StudentPrestudentDisableFields(true);
			StudentKontoDisableFields(true);
			StudentAkteDisableFields(true);
			StudentIODisableFields(true);
			StudentNoteDisableFields(true);
			document.getElementById('student-kontakt').setAttribute('src','');
			document.getElementById('student-betriebsmittel').setAttribute('src','');
			StudentAbschlusspruefungDisableFields(true);
		}
		catch(e)
		{
			debug(e);
		}
	}
}

function onFachbereichSelect(event)
{
	var tree=document.getElementById('tree-fachbereich');
	//Wenn nichts markiert wurde -> beenden
	if(tree.currentIndex==-1)
		return;

	var row = { };
    var col = { };
    var child = { };

    tree.treeBoxObject.getCellAt(event.pageX, event.pageY, row, col, child)

    //Wenn es keine Row ist sondern ein Header oder Scrollbar dann abbrechen
    if (!col.value)
       	return false;

    //Wenn eine andere row markiert ist als angeklickt wurde -> beenden.
	//Dies kommt vor wenn ein Subtree geoeffnet wird
	if(row.value!=tree.currentIndex)
		return;

	col = tree.columns ? tree.columns["fachbereich-treecol-kurzbz"] : "fachbereich-treecol-kurzbz";
	var kurzbz=tree.view.getCellText(tree.currentIndex,col);

	col = tree.columns ? tree.columns["fachbereich-treecol-uid"] : "fachbereich-treecol-uid";
	var uid=tree.view.getCellText(tree.currentIndex,col);
	
	//Wenn auf einen Mitarbeiter geklickt wird, dann die kurzbz vom uebergeordneten
	//Fachbereich holen
	if(uid!='')
	{
		idx = tree.view.getParentIndex(tree.currentIndex);
		col = tree.columns ? tree.columns["fachbereich-treecol-kurzbz"] : "fachbereich-treecol-kurzbz";
		var kurzbz=tree.view.getCellText(idx,col);
	}
	// Lehrveranstaltung
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	try
	{
		url = '<?php echo APP_ROOT; ?>rdf/lehrveranstaltung_einheiten.rdf.php?fachbereich_kurzbz='+kurzbz+'&uid='+uid+'&'+gettimestamp();
		var treeLV=document.getElementById('lehrveranstaltung-tree');

		try
		{
			LvTreeDatasource.removeXMLSinkObserver(LvTreeSinkObserver);
			treeLV.builder.removeListener(LvTreeListener);
		}
		catch(e)
		{}
		
		//Alte DS entfernen
		var oldDatasources = treeLV.database.GetDataSources();
		while(oldDatasources.hasMoreElements())
		{
			treeLV.database.RemoveDataSource(oldDatasources.getNext());
		}

		var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
		LvTreeDatasource = rdfService.GetDataSource(url);
		LvTreeDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
		LvTreeDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
		treeLV.database.AddDataSource(LvTreeDatasource);
		LvTreeDatasource.addXMLSinkObserver(LvTreeSinkObserver);
		treeLV.builder.addListener(LvTreeListener);
		document.getElementById('lehrveranstaltung-toolbar-lehrauftrag').hidden=true;
	}
	catch(e)
	{
		debug(e);
	}
}

function onOrtSelect()
{
	var contentFrame=document.getElementById('iframeTimeTableWeek');
	var treeOrt=document.getElementById('tree-ort');
	var ort=treeOrt.view.getCellText(treeOrt.currentIndex,"ort_kurzbz");
	var daten=window.TimeTableWeek.document.getElementById('TimeTableWeekData');
	var datum=parseInt(daten.getAttribute("datum"));

	var attributes="?type=ort&ort="+ort+"&datum="+datum;
	var url = "<?php echo APP_ROOT; ?>content/timetable-week.xul.php";
	url+=attributes;
	if (url)
		contentFrame.setAttribute('src', url);
}

function onLektorSelect(event)
{
	var tree=document.getElementById('tree-lektor');
	//Wenn nichts markiert wurde -> beenden
	if(tree.currentIndex==-1)
		return;

	var row = { };
    var col = { };
    var child = { };

    tree.treeBoxObject.getCellAt(event.pageX, event.pageY, row, col, child)

    //Wenn es keine Row ist sondern ein Header oder Scrollbar dann abbrechen
    if (!col.value)
       	return false;

    //Wenn eine andere row markiert ist als angeklickt wurde -> beenden.
	//Dies kommt vor wenn ein Subtree geoeffnet wird
	if(row.value!=tree.currentIndex)
		return;

	col = tree.columns ? tree.columns["uid"] : "uid";
	
	var uid=tree.view.getCellText(tree.currentIndex,col);

	var stg_idx = tree.view.getParentIndex(tree.currentIndex);
	//wenn direkt ein studiengang markiert wurde dann abbrechen
	if(stg_idx==-1)
		return;
		
	var col = tree.columns ? tree.columns["studiengang_kz"] : "studiengang_kz";
	var stg_kz=tree.view.getCellText(stg_idx,col);

	document.getElementById('LehrveranstaltungEditor').setAttribute('stg_kz',stg_kz);
	document.getElementById('LehrveranstaltungEditor').setAttribute('uid',uid);

	// Lehrveranstaltung des Lektors laden
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	try
	{
		//alert(stg_kz);
		url = '<?php echo APP_ROOT; ?>rdf/lehrveranstaltung_einheiten.rdf.php?stg_kz='+stg_kz+'&uid='+uid+'&'+gettimestamp();
		var treeLV=document.getElementById('lehrveranstaltung-tree');

		//Alte DS entfernen
		var oldDatasources = treeLV.database.GetDataSources();
		try
		{
			LvTreeDatasource.removeXMLSinkObserver(LvTreeSinkObserver);
			treeLV.builder.removeListener(LvTreeListener);
		}
		catch(e)
		{}
		
		while(oldDatasources.hasMoreElements())
		{
			treeLV.database.RemoveDataSource(oldDatasources.getNext());
		}

		var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
		LvTreeDatasource = rdfService.GetDataSource(url);
		LvTreeDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
		LvTreeDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
		treeLV.database.AddDataSource(LvTreeDatasource);
		LvTreeDatasource.addXMLSinkObserver(LvTreeSinkObserver);
		treeLV.builder.addListener(LvTreeListener);
		document.getElementById('lehrveranstaltung-toolbar-lehrauftrag').hidden=false;
	}
	catch(e)
	{
		debug(e);
	}
}

function loadURL(event)
{
        var contentFrame = document.getElementById('contentFrame');
        var url = event.target.getAttribute('value');

        if (url) contentFrame.setAttribute('src', url);
};

function parseRDFString(str, url)
{

	try {
		netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	} catch(e) {
		alert(e);
		return;
	}

  var memoryDS = Components.classes["@mozilla.org/rdf/datasource;1?name=in-memory-datasource"].createInstance(Components.interfaces.nsIRDFDataSource);

  var ios=Components.classes["@mozilla.org/network/io-service;1"].getService(Components.interfaces.nsIIOService);
  baseUri=ios.newURI(url,null,null);

  var parser=Components.classes["@mozilla.org/rdf/xml-parser;1"].createInstance(Components.interfaces.nsIRDFXMLParser);
  parser.parseString(memoryDS,baseUri,str);

  return memoryDS;
}

// ****
// * Liefert eine HTML Liste mit den Koordinatorstunden
// * fuer einen Fachbereich
// ****
function StatistikPrintKoordinatorstunden()
{
	tree = document.getElementById('tree-fachbereich');

	try
	{
		//Fachbereich holen
		var col;
		col = tree.columns ? tree.columns["fachbereich-treecol-kurzbz"] : "fachbereich-treecol-kurzbz";
		var fachbereich_kurzbz=tree.view.getCellText(tree.currentIndex,col);
		
		col = tree.columns ? tree.columns["fachbereich-treecol-uid"] : "fachbereich-treecol-uid";
		var uid=tree.view.getCellText(tree.currentIndex,col);
	
		if(uid=='')
			window.open('<?php echo APP_ROOT ?>content/statistik/koordinatorstunden.php?fachbereich_kurzbz='+fachbereich_kurzbz,'Koordinatorstunden');
		else
			alert('Bitte einen Fachbereich auswaehlen');
	}
	catch(e)
	{
		alert('Bitte einen Fachbereich auswaehlen');
		return false;
	}
}

// ****
// * Erstellt das PDF File fuer die Lehrauftraege
// * Studiengang muss ausgewaehlt sein
// ****
function StatistikPrintLehrauftraege()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);

	var ss = document.getElementById('statusbarpanel-semester').label;
	window.open('<?php echo APP_ROOT ?>content/pdfExport.php?xml=lehrauftrag.xml.php&xsl=Lehrauftrag&stg_kz='+studiengang_kz+'&ss='+ss,'Lehrauftrag');
}

// ****
// * Liefert eine HTML Liste mit Uebersicht der Lehrauftraege.
// * Studiengang und optional Semester muss gewaehlt sein.
// ****
function StatistikPrintLVPlanung()
{

	if(document.getElementById('menu-content-tabs').selectedItem == document.getElementById('tab-verband'))
	{
		tree = document.getElementById('tree-verband');

		if(tree.currentIndex==-1)
		{
			alert('Bitte zuerst einen Studiengang auswaehlen');
			return;
		}

		//Studiengang und Semester holen
		var col;
		col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
		var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
		col = tree.columns ? tree.columns["sem"] : "sem";
		var semester=tree.view.getCellText(tree.currentIndex,col);
		var url = '<?php echo APP_ROOT ?>content/statistik/lvplanung.php?studiengang_kz='+studiengang_kz+'&semester='+semester;
	}
	else if(document.getElementById('menu-content-tabs').selectedItem == document.getElementById('tab-fachbereich'))
	{
		tree = document.getElementById('tree-fachbereich');

		if(tree.currentIndex==-1)
		{
			alert('Bitte zuerst einen Fachbereich auswaehlen');
			return;
		}

		//Fachbereich holen
		var col;
		col = tree.columns ? tree.columns["fachbereich-treecol-kurzbz"] : "fachbereich-treecol-kurzbz";
		var fachbereich_kurzbz=tree.view.getCellText(tree.currentIndex,col);
		var url = '<?php echo APP_ROOT ?>content/statistik/lvplanung.php?fachbereich_kurzbz='+fachbereich_kurzbz;
	}
	else if(document.getElementById('menu-content-tabs').selectedItem == document.getElementById('tab-lektor'))
	{
		tree = document.getElementById('tree-lektor');

		if(tree.currentIndex==-1)
		{
			alert('Bitte zuerst einen Mitarbeiter auswaehlen');
			return;
		}

		//UID holen
		var col;
		col = tree.columns ? tree.columns["uid"] : "uid";
		var uid=tree.view.getCellText(tree.currentIndex,col);
		var url = '<?php echo APP_ROOT ?>content/statistik/lvplanung.php?uid='+uid;
	}
	window.open(url,'LV-Planung');
}

// ****
// * Liefert eine Excel Liste mit Uebersicht der Lehrauftraege.
// * Studiengang oder Fachbereich muss gewaehlt sein
// ****
function StatistikPrintLVPlanungExcel()
{
	var studiensemester=getStudiensemester();
	
	if(document.getElementById('menu-content-tabs').selectedItem == document.getElementById('tab-verband'))
	{
		tree = document.getElementById('tree-verband');

		if(tree.currentIndex==-1)
		{
			alert('Bitte zuerst einen Studiengang auswaehlen');
			return;
		}

		//Studiengang und Semester holen
		var col;
		col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
		var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
		col = tree.columns ? tree.columns["sem"] : "sem";
		var semester=tree.view.getCellText(tree.currentIndex,col);
		var url = '<?php echo APP_ROOT ?>content/statistik/lvplanung.xls.php?studiengang_kz='+studiengang_kz+'&semester='+semester+'&studiensemester_kurzbz='+studiensemester;
	}
	else if(document.getElementById('menu-content-tabs').selectedItem == document.getElementById('tab-fachbereich'))
	{
		tree = document.getElementById('tree-fachbereich');

		if(tree.currentIndex==-1)
		{
			alert('Bitte zuerst einen Fachbereich auswaehlen');
			return;
		}

		//Fachbereich holen
		var col;
		col = tree.columns ? tree.columns["fachbereich-treecol-kurzbz"] : "fachbereich-treecol-kurzbz";
		var fachbereich_kurzbz=tree.view.getCellText(tree.currentIndex,col);
		var url = '<?php echo APP_ROOT ?>content/statistik/lvplanung.xls.php?institut='+fachbereich_kurzbz+'&studiensemester_kurzbz='+studiensemester;
	}
	else if(document.getElementById('menu-content-tabs').selectedItem == document.getElementById('tab-lektor'))
	{
		tree = document.getElementById('tree-lektor');

		if(tree.currentIndex==-1)
		{
			alert('Bitte zuerst einen Mitarbeiter auswaehlen');
			return;
		}

		//UID holen
		var col;
		col = tree.columns ? tree.columns["uid"] : "uid";
		var uid=tree.view.getCellText(tree.currentIndex,col);
		var url = '<?php echo APP_ROOT ?>content/statistik/lvplanung..xls.php?uid='+uid+'&studiensemester_kurzbz='+studiensemester;
	}
	window.open(url,'LV-Planung');
}

// ****
// * Erstellt ein Excel File mit der Uebersicht
// * ueber alle Lektoren und deren Kosten eines Studienganges
// ****
function StatistikPrintLehrauftragsliste()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang und Semester holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["sem"] : "sem";
	var semester=tree.view.getCellText(tree.currentIndex,col);

	window.open('<?php echo APP_ROOT ?>content/statistik/lehrauftragsliste_gst.xls.php?studiengang_kz='+studiengang_kz+'&semester='+semester,'Lehrauftragsliste');
}

// ****
// * Erstellt ein Excelfile mit den Daten der Abschlusspruefung
// ****
function StatistikPrintProjektarbeit()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang und Semester holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["sem"] : "sem";
	var semester=tree.view.getCellText(tree.currentIndex,col);

	if(studiengang_kz!='')
		window.open('<?php echo APP_ROOT ?>content/statistik/projektarbeitexport.xls.php?studiengang_kz='+studiengang_kz+'&semester='+semester+'&studiensemester_kurzbz='+getStudiensemester(),'Excel');
	else
		alert('Bitte zuerst Studiengang / Semester auswaehlen');
}

// ****
// * Liefert eine HTML Liste mit Uebersicht der Lehrauftraege.
// * Studiengang und optional Semester muss gewaehlt sein.
// ****
function StatistikPrintAbschlusspruefung()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang und Semester holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["sem"] : "sem";
	var semester=tree.view.getCellText(tree.currentIndex,col);

	if(studiengang_kz!='')
		window.open('<?php echo APP_ROOT ?>content/statistik/abschlusspruefungexport.xls.php?studiengang_kz='+studiengang_kz+'&semester='+semester+'&studiensemester_kurzbz='+getStudiensemester(),'Excel');
	else
		alert('Bitte zuerst Studiengang / Semester auswaehlen');
}

// ****
// * Liefert eine Excel Checkliste fuer die Fehlenden Dokumente
// * Studiengang muss gewaehlt sein.
// ****
function StatistikPrintFehlendeDokumente()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);

	if(studiengang_kz!='')
		window.open('<?php echo APP_ROOT ?>content/statistik/dokumente.xls.php?studiengang_kz='+studiengang_kz+'&studiensemester_kurzbz='+getStudiensemester(),'Excel');
	else
		alert('Bitte zuerst Studiengang auswaehlen');
}

// ****
// * Liefert eine HTML Liste mit Uebersicht ueber die eingetragenen Noten
// * Studiengang und optional Semester muss gewaehlt sein.
// ****
function StatistikPrintNotenspiegel(typ)
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang und Semester holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["sem"] : "sem";
	var semester=tree.view.getCellText(tree.currentIndex,col);

	window.open('<?php echo APP_ROOT ?>content/statistik/notenspiegel.php?studiengang_kz='+studiengang_kz+'&semester='+semester+'&typ='+typ,'Notenspiegel');
}

// ****
// * Liefert eine statistik ueber die Anzahl der Interessenten/Bewerber Studenten
// ****
function StatistikPrintBewerberstatistik()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/bewerberstatistik.php?stsem='+stsem,'Bewerberstatistik');
}

// ****
// * Liefert eine statistik ueber die Anzahl der Abgewiesenen, Abbrecher, Unterbrecher und Absolventen
// ****
function StatistikPrintAbgaengerstatistik()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/abgaengerstatistik.php?stsem='+stsem,'Abgaengerstatistik');
}

// ****
// * Liefert eine Liste mit den Studenten die den OEH-Beitrag zahlen muessen / gezahlt haben
// ****
function StatistikPrintOehBeitrag()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/oeh_beitraege.xls.php?studiensemester_kurzbz='+stsem,'OEHBeitraege');

}

// ****
// * Liefert eine statistik ueber die Anzahl der Absolventen pro Studiengang
// ****
function StatistikPrintAbsolventenstatistik()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/absolventenstatistik.php?stsem='+stsem,'Absolventenstatistik');
}

// ****
// * Liefert eine statistik ueber die Anzahl und Verteilung der Studenten auf die Studiengaenge
// ****
function StatistikPrintStudentenstatistik()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/studentenstatistik.php?stsem='+stsem,'Studentenstatistik');
}

// ****
// * Liefert eine statistik ueber die Lektorenverteilung auf die Institute
// ****
function StatistikPrintLektorenstatistik()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/lektorenstatistik.php?stsem='+stsem,'Lektorenstatistik');
}

// ****
// * Liefert eine statistik ueber die Institutszuordnungen und Aufteilung auf intern/extern
// ****
function StatistikPrintMitarbeiterstatistik()
{
	var stsem = getStudiensemester();

	window.open('<?php echo APP_ROOT ?>content/statistik/mitarbeiterstatistik.php?stsem='+stsem,'Mitarbeiterstatistik');
}

// ****
// * Liefert eine Stromanalyse der Studenten
// ****
function StatistikPrintStromanalyse()
{
	var stsem = getStudiensemester();
	if(stsem.startsWith('WS'))
		param = "?studiensemester_kurzbz="+stsem;
	else
		param ='';
	window.open('<?php echo APP_ROOT ?>content/statistik/bama_stromanalyse.php'+param,'Stromanalyse');
}

function StatistikPrintStudentExportExtended()
{
	var tree = document.getElementById('student-tree');
	var data='';
	//Wenn nichts markiert wurde -> alle exportieren
	if(tree.currentIndex==-1)
	{
		if(tree.view)
			var items = tree.view.rowCount; //Anzahl der Zeilen ermitteln
		else
			return false;
			
		for (var v=0; v < items; v++)
		{
			prestudent_id = getTreeCellText(tree, 'student-treecol-prestudent_id', v);
			data = data+';'+prestudent_id;
		}
	}
	else
	{	
		var start = new Object();
		var end = new Object();
		var numRanges = tree.view.selection.getRangeCount();
		var paramList= '';
		var anzahl=0;
	
		//alle markierten personen holen
		for (var t = 0; t < numRanges; t++)
		{
	  		tree.view.selection.getRangeAt(t,start,end);
			for (var v = start.value; v <= end.value; v++)
			{
				prestudent_id = getTreeCellText(tree, 'student-treecol-prestudent_id', v);
				data = data+';'+prestudent_id;
			}
		}
	}
	
	stsem = getStudiensemester();
	action = '<?php echo APP_ROOT; ?>content/statistik/studentenexportextended.xls.php?studiensemester_kurzbz='+stsem;
	OpenWindowPost(action, data);
}

// ****
// * Zeigt HTML Seite zur Bearbeitung der Reihungstests an
// ****
function ExtrasShowReihungstest()
{
	window.open('<?php echo APP_ROOT ?>vilesci/stammdaten/reihungstestverwaltung.php','Reihungstest','');
}

// ****
// * Zeut HTML Seite zur Bearbeitung der Lehrveranstaltungen an
// ****
function ExtrasShowLVverwaltung()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang und Semester holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
	col = tree.columns ? tree.columns["sem"] : "sem";
	var semester=tree.view.getCellText(tree.currentIndex,col);
	
	window.open('<?php echo APP_ROOT ?>vilesci/lehre/lehrveranstaltung.php?stg_kz='+studiengang_kz+'&semester='+semester,'Lehrveranstaltungen','');
}

// ****
// * Zeigt HTML Seite zur Bearbeitung der Firmen an
// ****
function ExtrasShowFirmenverwaltung()
{
	window.open('<?php echo APP_ROOT ?>vilesci/stammdaten/firma_frameset.html','Firma','');
}

// ****
// * Zeigt HTML Seite zum Eintragen von Projektarbeitsnoten an
// ****
function ExtrasShowProjektarbeitsBenotung()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
		
	window.open('<?php echo APP_ROOT ?>vilesci/lehre/projektarbeitsbenotung.php?stg_kz='+studiengang_kz,'Projektarbeitsbenotung','');
}

// ****
// * Zeigt HTML Seite zum Eintragen von Abgabeterminen fuer Projektarbeit an
// ****
function ExtrasShowProjektarbeitsabgaben()
{
	tree = document.getElementById('tree-verband');

	if(tree.currentIndex==-1)
	{
		alert('Bitte zuerst einen Studiengang auswaehlen');
		return;
	}

	//Studiengang holen
	var col;
	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var studiengang_kz=tree.view.getCellText(tree.currentIndex,col);
		
	window.open('<?php echo APP_ROOT ?>vilesci/lehre/abgabe_assistenz_frameset.php?stg_kz='+studiengang_kz,'Projektarbeitsabgaben','');
}

// ****
// * Zeigt HTML Seite zur Bearbeitung der Gruppen an
// ****
function ExtrasShowGruppenverwaltung()
{
	window.open('<?php echo APP_ROOT ?>vilesci/stammdaten/lvbgruppenverwaltung.php','Gruppen','');
}

// ****
// * Zeigt HTML Seite zur Bearbeitung der Lehrfaecher an
// ****
function ExtrasShowLehrfachverwaltung()
{
	window.open('<?php echo APP_ROOT ?>vilesci/lehre/lehrfach.php','Lehrfach','');
}

// ****
// * Zeigt HTML Seite mit Lektorenzuordnung zu Instituten
// ****
function ExtrasShowLektorenzuordnunginstitute()
{
	window.open('<?php echo APP_ROOT ?>vilesci/personen/institutsliste.php','Lektorenzuordnung-Institute','');
}

// ****
// * Zeigt HTML Seite zur Uebernahme der Preinteressenten
// ****
function ExtrasShowPreinteressentenuebernahme()
{
	window.open('<?php echo APP_ROOT ?>vilesci/personen/preinteressent_uebernahme.php','PreinteressentUebernahme','');
}

// ****
// * Zeigt eine Seite zum Importieren der Funktionen aus der vorherigen BISMeldung
// ****
function BISMitarbeiterImport()
{
	window.open('<?php echo APP_ROOT ?>vilesci/bis/personalimport.php','Import','');
}

// ****
// * Oeffnet Script zum generieren der BIS-Meldung
// ****
function BISMitarbeiterExport()
{
	window.open('<?php echo APP_ROOT ?>vilesci/bis/personalmeldung.php','Generieren','');
}

// ****
// * Oeffnet Script zum Checken der Verwendung
// ****
function BISMitarbeiterCheckVerwendung()
{
	window.open('<?php echo APP_ROOT ?>vilesci/bis/checkverwendung.php','CheckVerwendung','');
}

// ****
// * oeffnet Script zum Checken der Funktion
// ****
function BISMitarbeiterCheckFunktion()
{
	window.open('<?php echo APP_ROOT ?>vilesci/bis/checkfunktion.php','CheckFunktion','');
}

// ****
// * oeffnet Script zum Checken der Studentendaten
// ****
function BISStudentenPlausicheck()
{
	var tree=document.getElementById('tree-verband');

	//Wenn nichts markiert wurde -> beenden
	if(tree.currentIndex==-1)
	{
		alert('Bitte einen Studiengang auswaehlen');
		return;
	}

	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var stg_kz=tree.view.getCellText(tree.currentIndex,col);

	if(stg_kz!='')
		window.open('<?php echo APP_ROOT ?>vilesci/bis/studentenmeldung.php?plausi=J&stg_kz='+stg_kz,'Datencheck','');
	else
		alert('Bitte einen Studiengang auswaehlen');
}

// ****
// * oeffnet Script zum BIS-Export der Studentendaten
// ****
function BISStudentenExport()
{
	var tree=document.getElementById('tree-verband');

	//Wenn nichts markiert wurde -> beenden
	if(tree.currentIndex==-1)
	{
		alert('Bitte einen Studiengang auswaehlen');
		return;
	}

	col = tree.columns ? tree.columns["stg_kz"] : "stg_kz";
	var stg_kz=tree.view.getCellText(tree.currentIndex,col);

	if(stg_kz!='')
		window.open('<?php echo APP_ROOT ?>vilesci/bis/studentenmeldung.php?stg_kz='+stg_kz,'StudentenMeldung','');
	else
		alert('Bitte einen Studiengang auswaehlen');
}

// ****
// * Oeffnet den About Dialog
// ****
function OpenAboutDialog()
{
	window.open('<?php echo APP_ROOT ?>content/about.xul.php','About','height=520,width=500,left=350,top=350,hotkeys=0,resizable=yes,status=no,scrollbars=yes,toolbar=no,location=no,menubar=no,dependent=yes');
}

// ****
// * Oeffnet das Handbuch
// ****
function OpenManual()
{
	window.open('<?php echo APP_ROOT ?>content/FASo.pdf','Manual');
}

// ****
// * Oeffnet die ToDo Liste
// ****
function OpenToDoDialog()
{
	window.open('<?php echo APP_ROOT ?>content/ToDo_FASo.html','ToDo','height=600,width=600,left=150,top=150,hotkeys=0,resizable=yes,status=no,scrollbars=yes,toolbar=no,location=no,menubar=no,dependent=yes');
}

// ****
// * Oeffnet ALVS-Statistik
// ****
function StatistikPrintALVSStatistik(format)
{
	window.open('<?php echo APP_ROOT ?>content/statistik/alvsstatistik.php?format='+format,'ALVS-Statistik','');
}

// ****
// * Oeffnet die LVPlanunggesamtSJ Statistik
// ****
function StatistikPrintLvPlanungGesamtSJ()
{
	window.open('<?php echo APP_ROOT ?>content/statistik/lvplanunggesamtsj.php','LVPlanungGesamtSJ','');
}

// ****
// * Oeffnet Studenten/Semester Statistik
// ****
function StatistikPrintStudentenProSemester(format)
{
	window.open('<?php echo APP_ROOT ?>content/statistik/studentenprosemester.php?format='+format,'Studenten/Semester','');
}

// ****
// * Wenn der Tab Mitarbeiter aktiviert ist und der Prestudent-, Student- oder Lehrveranstaltungstab
// * markiert wird, dann wird im Menue auf den Verband Tag gewechselt
// ****
function ChangeTabsToVerband()
{
	if(document.getElementById('menu-content-tabs').selectedItem==document.getElementById('tab-menu-mitarbeiter'))
		document.getElementById('menu-content-tabs').selectedItem=document.getElementById('tab-verband');
}

// ****
// * Wenn der Tab Fachbereich oder Lektor gewaehlt wird, dann wird auf den Tab Lehrveranstaltung gewechselt
// ****
function ChangeTabsToLehrveranstaltung()
{
	document.getElementById('main-content-tabs').selectedItem=document.getElementById('tab-lfvt');
}

// ****
// * Wenn der Tab Mitarbeiter Markiert ist, und auf den Tab Verband geklickt wird,
// * dann wird der StudententTab markiert
// ****
function ChangeTabVerband()
{
	if(document.getElementById('main-content-tabs').selectedItem==document.getElementById('tab-mitarbeiter'))
		document.getElementById('main-content-tabs').selectedItem=document.getElementById('tab-studenten');
}

// ****
// * Aendert die Variable kontofilterstg
// * Wenn kontofilterstg=true dann werden nur die Buchungen aus dem
// * aktuellen Studiengang angezeigt
// ****
function EinstellungenKontoFilterStgChange()
{
	item = document.getElementById('menu-prefs-kontofilterstg');

	if(item.getAttribute('checked')=='true')
		checked='true';
	else
		checked='false';

	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");

	// Request absetzen

	var url = '<?php echo APP_ROOT ?>content/fasDBDML.php';

	var req = new phpRequest(url,'','');

	req.add('type', 'variablechange');
	req.add('kontofilterstg', checked);

	var response = req.executePOST();

	var val =  new ParseReturnValue(response)

	if (!val.dbdml_return)
	{
		if(val.dbdml_errormsg=='')
			alert(response)
		else
			alert(val.dbdml_errormsg)
	}
	else
	{
		//Statusbar setzen
   		document.getElementById("statusbarpanel-text").label = "Variable wurde erfolgreich geaendert";

   		//Ansichten Refreshen
   		try
   		{
   			//Konto tree Refreshen
   		}
   		catch(e)
   		{
   			debug('catch: '+e);
   		}
	}

	return true;
}

// ****
// * Druckt das AccountInfoBlatt
// ****
function PrintAccountInfoBlatt()
{

	if(document.getElementById('main-content-tabs').selectedItem==document.getElementById('tab-studenten'))
	{
		//STUDENTEN
		var tree = document.getElementById('student-tree');
		var data='';

		var start = new Object();
		var end = new Object();
		var numRanges = tree.view.selection.getRangeCount();
		var paramList= '';
		var error=0;

		//alle markierten personen holen
		for (var t = 0; t < numRanges; t++)
		{
	  		tree.view.selection.getRangeAt(t,start,end);
			for (var v = start.value; v <= end.value; v++)
			{
				col = tree.columns ? tree.columns["student-treecol-uid"] : "student-treecol-uid";
				uid = tree.view.getCellText(v,col);
				if(uid!='')
					data = data+';'+uid;
				else
					error = error+1;
			}
		}
	}
	else
	{
		//MITARBEITER
		var tree = document.getElementById('mitarbeiter-tree');
		var data='';

		var start = new Object();
		var end = new Object();
		var numRanges = tree.view.selection.getRangeCount();
		var paramList= '';
		var error=0;

		//alle markierten personen holen
		for (var t = 0; t < numRanges; t++)
		{
	  		tree.view.selection.getRangeAt(t,start,end);
			for (var v = start.value; v <= end.value; v++)
			{
				col = tree.columns ? tree.columns["mitarbeiter-treecol-uid"] : "mitarbeiter-treecol-uid";
				uid = tree.view.getCellText(v,col);
				if(uid!='')
					data = data+';'+uid;
				else
					error = error+1;
			}
		}
	}

	if(data!='')
	{
		if(error>0)
			alert(error+' der ausgewaehlten Personen haben keinen Account');
		action = '<?php echo APP_ROOT; ?>content/pdfExport.php?xsl=AccountInfo&xml=accountinfoblatt.xml.php&uid='+data;
		window.open(action,'AccountInfoBlatt','height=520,width=500,left=350,top=350,hotkeys=0,resizable=yes,status=no,scrollbars=yes,toolbar=no,location=no,menubar=no,dependent=yes');
	}
	else
	{
		alert('Bitte zuerst Personen Auswaehlen');
	}
}

// ****
// * Aktualisiert den Fachbereich Tree
// ****
function FachbereichTreeRefresh()
{
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	tree = document.getElementById('tree-fachbereich');
	
	var oldDatasources = tree.database.GetDataSources();	
	while(oldDatasources.hasMoreElements())
	{
		tree.database.RemoveDataSource(oldDatasources.getNext());
	}
	tree.builder.rebuild();
	
	url = '<?php echo APP_ROOT; ?>rdf/fachbereich_menue.rdf.php?'+gettimestamp();
	var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
	var fb_datasource = rdfService.GetDataSource(url);
	fb_datasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
	fb_datasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
	tree.database.AddDataSource(fb_datasource);
}