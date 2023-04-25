<?php
namespace vertragsbestandteil;

use vertragsbestandteil\Vertragsbestandteil;
use vertragsbestandteil\VertragsbestandteilFactory;

class VertragsbestandteilFreitext extends Vertragsbestandteil
{
	protected $freitexttyp_kurzbz;
	protected $titel;
	protected $anmerkung;
	
	public function __construct()
	{
		parent::__construct();
		$this->setVertragsbestandteiltyp_kurzbz(
			VertragsbestandteilFactory::VERTRAGSBESTANDTEIL_FREITEXT);
	}
	
	public function hydrateByStdClass($data)
	{
		parent::hydrateByStdClass($data);
		isset($data->freitexttyp) && $this->setFreitexttypKurzbz($data->freitexttyp);
		isset($data->titel) && $this->setTitel($data->titel);
		isset($data->freitext) && $this->setAnmerkung($data->freitext);
	}
		
	public function toStdClass(): \stdClass
	{
		$tmp = array(
			'vertragsbestandteil_id' => $this->getVertragsbestandteil_id(),
			'freitexttyp_kurzbz' => $this->getFreitexttypKurzbz(), 
			'titel' => $this->getTitel(), 
			'anmerkung' => $this->getAnmerkung()
		);
		
		$tmp = array_filter($tmp, function($v) {
			return !is_null($v);
		});
		
		return (object) $tmp;
	}
	
	public function __toString() 
	{
		$txt = <<<EOTXT
		anmerkung: {$this->getAnmerkung()}
		titel: {$this->getTitel()}
		freitexttyp_kurzbz: {$this->getFreitexttypKurzbz()}

EOTXT;
		return parent::__toString() . $txt;
	}

	/**
	 * Get the value of anmerkung
	 */
	public function getAnmerkung()
	{
		return $this->anmerkung;
	}

	/**
	 * Set the value of anmerkung
	 */
	public function setAnmerkung($anmerkung): self
	{
		$this->anmerkung = $anmerkung;

		return $this;
	}

	/**
	 * Get the value of titel
	 */
	public function getTitel()
	{
		return $this->titel;
	}

	/**
	 * Set the value of titel
	 */
	public function setTitel($titel): self
	{
		$this->titel = $titel;

		return $this;
	}

	/**
	 * Get the value of freitexttyp_kurzbz
	 */
	public function getFreitexttypKurzbz()
	{
		return $this->freitexttyp_kurzbz;
	}

	/**
	 * Set the value of freitexttyp_kurzbz
	 */
	public function setFreitexttypKurzbz($freitexttyp_kurzbz): self
	{
		$this->freitexttyp_kurzbz = $freitexttyp_kurzbz;

		return $this;
	}
	
	public function validate()
	{
		return parent::validate();
	}
}
