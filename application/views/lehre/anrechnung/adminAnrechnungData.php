<?php

$query = '
	SELECT *
    FROM lehre.tbl_anrechnungszeitraum
    ORDER BY anrechnungstart DESC
';

$filterWidgetArray = array(
	'query' => $query,
	'bootstrapVersion' => 5,
	'tableUniqueId' => 'adminAnrechnung',
	'requiredPermissions' => 'lehre/anrechnungszeitfenster',
	'datasetRepresentation' => 'tabulator',
	'columnsAliases' => array(
		'AzrID',
		ucfirst($this->p->t('lehre', 'studiensemester')),
		ucfirst($this->p->t('anrechnung', 'anrechnungszeitraumStart')),
		ucfirst($this->p->t('anrechnung', 'anrechnungszeitraumEnde')),
		ucfirst($this->p->t('ui', 'bearbeitetAm')),
		ucfirst($this->p->t('ui', 'bearbeitetVon')),
	),
	'datasetRepOptions' => '{
		
		layout: "fitColumns",           
		persistentLayout:true,
		autoResize: false, 				// prevent auto resizing of table (false to allow adapting table size when cols are (de-)activated
        index: "anrechnungszeitraum_id",         // assign specific column as unique id (important for row indexing)
        selectable: false,                  // allow row selection
		tableWidgetHeader: true,
		columnDefaults:{
			headerFilterPlaceholder: " ",
		}
	 }',
	'datasetRepFieldsDefs' => '{
		anrechnungszeitraum_id: {visible: false, headerFilter:"input"},
		studiensemester_kurzbz: {headerFilter:"input"},
		anrechnungstart:        {headerFilter:"input", formatter: formatDate},
		anrechnungende:         {headerFilter:"input", formatter: formatDate},
		insertamum:             {visible: false, headerFilter:"input"},
		insertvon:              {visible: false, headerFilter:"input"}
	 }'
);

echo $this->widgetlib->widget('TableWidget', $filterWidgetArray);