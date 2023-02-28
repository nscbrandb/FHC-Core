<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once('PlausiChecker.php');

/**
 *
 */
class DatumAbschlusspruefungFehlt extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		$results = array();

		// pass parameters needed for plausicheck
		$studiensemester_kurzbz = isset($params['studiensemester_kurzbz']) ? $params['studiensemester_kurzbz'] : null;
		$studiengang_kz = isset($params['studiengang_kz']) ? $params['studiengang_kz'] : null;

		// get all students failing the plausicheck
		$prestudentRes = $this->_ci->plausichecklib->getDatumAbschlusspruefungFehlt($studiensemester_kurzbz, $studiengang_kz);

		if (isError($prestudentRes)) return $prestudentRes;

		if (hasData($prestudentRes))
		{
			$prestudents = getData($prestudentRes);

			// populate results with data necessary for writing issues
			foreach ($prestudents as $prestudent)
			{
				$results[] = array(
					'person_id' => $prestudent->person_id,
					'oe_kurzbz' => $prestudent->prestudent_stg_oe_kurzbz,
					'fehlertext_params' => array(
						'prestudent_id' => $prestudent->prestudent_id,
						'abschlusspruefung_id' => $prestudent->abschlusspruefung_id
					),
					'resolution_params' => array('abschlusspruefung_id' => $prestudent->abschlusspruefung_id)
				);
			}
		}

		// return the results
		return success($results);
	}
}
