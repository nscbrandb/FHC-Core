<?php
class Notizdokument_model extends DB_Model
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'public.tbl_notiz_dokument';
		$this->pk= array('notiz_id' , 'dms_id');
	}
}