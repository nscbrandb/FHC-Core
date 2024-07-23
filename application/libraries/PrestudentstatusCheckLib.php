<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class PrestudentstatusCheckLib
{
	const INTERESSENT_STATUS = 'Interessent';
	const BEWERBER_STATUS = 'Bewerber';
	const AUFGENOMMENER_STATUS = 'Aufgenommener';
	const UNTERBRECHER_STATUS = 'Unterbrecher';
	const STUDENT_STATUS = 'Student';
	const DIPLOMAND_STATUS = 'Diplomand';
	const ABSOLVENT_STATUS = 'Absolvent';
	const ABBRECHER_STATUS = 'Abbrecher';

	private $_ci;
	private $_statusAbfolgeVorStudent = [self::INTERESSENT_STATUS, self::BEWERBER_STATUS, self::AUFGENOMMENER_STATUS];
	private $_endStatusArr = [self::ABSOLVENT_STATUS, self::ABBRECHER_STATUS];

	private $_cache_history = [];

	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance();

		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('person/Person_model', 'PersonModel');
		$this->_ci->load->model('crm/Prestudentstatus_model', 'PrestudentstatusModel');
		$this->_ci->load->model('crm/Prestudent_model', 'PrestudentModel');
		$this->_ci->load->model('crm/Student_model', 'StudentModel');
		$this->_ci->load->model('organisation/Studienplan_model', 'StudienplanModel');
		$this->_ci->load->model('codex/Bismeldestichtag_model', 'BismeldestichtagModel');
	}

	/**
	 * Checks if a status add is valid.
	 * @return object error if invalid
	 */
	public function checkStatusAdd(
		$prestudent_id,
		$status_kurzbz,
		$new_status_studiensemester_kurzbz,
		$new_status_datum,
		$new_status_ausbildungssemester,
		$new_studienplan_id
	) {
		$studentName = '';

		$nameRes = $this->_ci->PersonModel->loadPrestudent($prestudent_id);

		if (hasData($nameRes))
		{
			$nameData = getData($nameRes)[0];
			$studentName = $nameData->vorname.' '.$nameData->nachname;
		}

		// Datum des neuen Status darf nicht in Vergangenheit liegen, sonst Probleme wenn neues Datum < Bismeldedatum
		if (new DateTime($new_status_datum) < new DateTime('today'))
			return error($studentName . $this->_ci->p->t('lehre', 'error_entryInPast'));

		return $this->_checkIfValidStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_status_studiensemester_kurzbz,
			$new_status_datum,
			$new_status_ausbildungssemester,
			$new_studienplan_id
		);
	}

	/**
	 * Checks if a status update is valid.
	 * @return error if invalid
	 */
	public function checkStatusUpdate(
		$prestudent_id,
		$status_kurzbz,
		$new_status_studiensemester_kurzbz,
		$new_status_datum,
		$new_status_ausbildungssemester,
		$new_studienplan_id,
		$old_status_studiensemester,
		$old_status_ausbildungssemester
	) {

		return $this->_checkIfValidStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_status_studiensemester_kurzbz,
			$new_status_datum,
			$new_status_ausbildungssemester,
			$new_studienplan_id,
			$old_status_studiensemester,
			$old_status_ausbildungssemester
		);
	}

	/**
	 * Checks if a prestudent role already exists.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param string				$studiensemester_kurzbz
	 * @param integer				$ausbildungssemester
	 *
	 * @return stdClass
	 */
	public function checkIfExistingPrestudentRolle(
		$prestudent_id,
		$status_kurzbz,
		$tudiensemester_kurzbz,
		$ausbildungssemester
	) {
		$result =  $this->_ci->PrestudentstatusModel->checkIfExistingPrestudentRolle(
			$prestudent_id,
			$status_kurzbz,
			$tudiensemester_kurzbz,
			$ausbildungssemester
		);
		if (isError($result))
			return $result;

		return success(getData($result) != '1');
	}

	/**
	 * Checks if a student role already exists.
	 *
	 * @param integer				$prestudent_id
	 *
	 * @return stdClass
	 */
	public function checkIfExistingStudentRolle($prestudent_id)
	{
		$result = $this->_ci->StudentModel->checkIfExistingStudentRolle($prestudent_id);

		if (isError($result))
			return $result;

		return success(getData($result) != '0');
	}

	/**
	 * Check if Reihungstest was admitted
	 * @param integer $prestudent_id
	 * @return booleans $reihungstest_angetreten, error if not angetreten
	 */
	public function checkIfAngetreten($prestudent_id)
	{
		$result =  $this->_getApplicationData($prestudent_id);
		if(isError($result))
		{
			return getData($result);
		}
		$result =  current(getData($result));
		$studentName = trim ($result->vorname.' '.$result->nachname);

		if (!$result->reihungstestangetreten)
		return error($this->_ci->p->t('lehre', 'error_keinReihungstestverfahren', ['name' => $studentName]));

		return success($result->reihungstestangetreten);
	}

		/**
	 * Check if ZGV-Code is registered
	 * @param integer $prestudent_id
	 * @return booleans $zgv_code, error if not registered
	 */
	public function checkIfZGVEingetragen($prestudent_id, $typ=null)
	{
		$result =  $this->_getApplicationData($prestudent_id);
		if(isError($result))
		{
			return getData($result);
		}
		$result =  current(getData($result));
		$studentName = trim ($result->vorname.' '.$result->nachname);

		if ($typ && $typ=='m' && !$result->zgvmas_code)
		{
			return error($this->_ci->p->t('lehre', 'error_ZGVMasterNichtEingetragen', ['name' => $studentName]));
		}
		else
			return success($result->zgvmas_code);


		if(!$result->zgv_code)
		{
			return error($this->_ci->p->t('lehre', 'error_ZGVNichtEingetragen', ['name' => $studentName]));
		}

		return success($result->zgv_code);
	}

	/**
	 * Checks if a bewerber status already exists.
	 * @return error if invalid
	 * @return error if no bewerberstatus, success otherwise
	 */
	public function checkIfExistingBewerberstatus($prestudent_id)
	{
		$result =  $this->_getApplicationData($prestudent_id);
		if(isError($result))
		{
			return getData($result);
		}
		$result =  current(getData($result));
		$studentName = trim ($result->vorname.' '.$result->nachname);

		$result =  $this->_ci->PrestudentstatusModel->checkIfExistingBewerberstatus(
			$prestudent_id
		);
		if(isError($result))
		{
			return getData($result);
		}
		if(getData($result) == "0")
		{
			return error($this->_ci->p->t('lehre','error_keinBewerber', ['name' => $studentName]));
		}
		return success(getData($result));
	}

	/**
	 * Checks if status aufgenommen already exists.
	 * @return error if invalid
	 * @return error if no status Aufgenommen, success otherwise
	 */
	public function checkIfExistingAufgenommenerstatus($prestudent_id)
	{
		$result =  $this->_getApplicationData($prestudent_id);
		if(isError($result))
		{
			return getData($result);
		}
		$result =  current(getData($result));
		$studentName = trim ($result->vorname.' '.$result->nachname);

		$result =  $this->_ci->PrestudentstatusModel->checkIfExistingAufgenommenerstatus(
			$prestudent_id
		);
		if(isError($result))
		{
			return getData($result);
		}
		if(getData($result) == "0")
		{
			return error($this->_ci->p->t('lehre','error_keinAufgenommener', ['name' => $studentName]));
		}
		return success(getData($result));
	}

	/**
	 * Check if Bismeldestichtag erreicht
	 *
	 * @param DateTime				$statusDatum
	 * @param string				$studiensemester_kurzbz
	 *
	 * @return stdClass
	 */
	public function checkIfMeldestichtagErreicht($statusDatum, $studiensemester_kurzbz = null)
	{
		$result = $this->_ci->BismeldestichtagModel->checkIfMeldestichtagErreicht($statusDatum, $studiensemester_kurzbz);
		
		if (isError($result))
			return $result;
		
		return success(getData($result) == "1");
	}

	/**
	 * Runs all checks on Status History and saves it in cache.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param DateTime				$new_date
	 * @param string				$new_studiensemester_kurzbz
	 * @param integer				$new_ausbildungssemester
	 * @param string				$old_studiensemester_kurzbz
	 * @param integer				$old_ausbildungssemester
	 *
	 * @return stdClass
	 */
	protected function prepareStatusHistory(
		$prestudent_id,
		$status_kurzbz,
		$new_date,
		$new_studiensemester_kurzbz,
		$new_ausbildungssemester,
		$old_studiensemester_kurzbz,
		$old_ausbildungssemester
	) {
		// Generate key for caching
		$primary = implode('|', [
			$prestudent_id,
			$status_kurzbz,
			$new_date->format('Y-m-d'),
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		]);

		if (isset($this->_cache_history[$primary]))
			return $this->_cache_history[$primary];

		$this->_ci->load->model('crm/Prestudentstatus_model', 'PrestudentstatusModel');

		// Get the history
		$result = $this->_ci->PrestudentstatusModel->getHistoryWithNewOrEditedState(
			$prestudent_id,
			$status_kurzbz,
			$new_date,
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		);

		if (isError($result))
			return $result;

		if (!hasData($result))
			return error('This is impossible');

		$history = getData($result);
		$historyCount = count($history);

		// Run checks
		$checks = [
			'timesequence' => true,
			'laststatus' => true,
			'unterbrechersemester' => true,
			'abbrechersemester' => true,
			'diplomant' => true
		];

		for ($n = 0, $c = 1; $c < $historyCount; $n++, $c++) {
			if (!$checks['timesequence']
				&& !$checks['laststatus']
				&& !$checks['unterbrechersemester']
				&& !$checks['abbrechersemester']
				&& !$checks['diplomant']
			)
				break; // early out

			$next = $history[$n];
			$current = $history[$c];

			// Zeitabfolge ungültig?
			if ($checks['timesequence']
				&& $next->start < $current->start
			)
				$checks['timesequence'] = false;

			// Abbrecher- oder Absolventenstatus muss Endstatus sein
			if ($checks['laststatus']
				&& in_array($current->status_kurzbz, ['Absolvent', 'Abbrecher'])
			)
				$checks['laststatus'] = false;

			// wenn Unterbrecher auf Unterbrecher folgt, muss Ausbildungssemester gleich sein
			if ($checks['unterbrechersemester']
				&& $current->status_kurzbz == 'Unterbrecher'
				&& $next->status_kurzbz == 'Unterbrecher'
				&& $current->ausbildungssemester != $next->ausbildungssemester
			)
				$checks['unterbrechersemester'] = false;

			// wenn Abbrecher auf Unterbrecher folgt, muss Ausbildungssemester gleich sein
			if ($checks['abbrechersemester']
				&& $current->status_kurzbz == 'Unterbrecher'
				&& $next->status_kurzbz == 'Abbrecher'
				&& $current->ausbildungssemester != $next->ausbildungssemester
			)
				$checks['abbrechersemester'] = false;

			// keine Studenten nach Diplomand Status
			if ($checks['diplomant']
				&& $current->status_kurzbz == 'Diplomand'
				&& $next->status_kurzbz == 'Student'
			)
				$checks['diplomant'] = false;
		}

		$this->_cache_history[$primary] = success($checks);

		return success($checks);
	}

	/**
	 * Checks if the time sequence of the status history is valid.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param DateTime				$new_date
	 * @param string				$new_studiensemester_kurzbz
	 * @param integer				$new_ausbildungssemester
	 * @param string				$old_studiensemester_kurzbz
	 * @param integer				$old_ausbildungssemester
	 *
	 * @return stdClass
	 */
	public function checkStatusHistoryTimesequence(
		$prestudent_id,
		$status_kurzbz,
		$new_date,
		$new_studiensemester_kurzbz,
		$new_ausbildungssemester,
		$old_studiensemester_kurzbz,
		$old_ausbildungssemester
	) {
		$result = $this->prepareStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_date,
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		);

		if (isError($result))
			return $result;

		return success(getData($result)['timesequence']);
	}

	/**
	 * Checks if the last status of the status history is not Abbrecher or
	 * Absolvent.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param DateTime				$new_date
	 * @param string				$new_studiensemester_kurzbz
	 * @param integer				$new_ausbildungssemester
	 * @param string				$old_studiensemester_kurzbz
	 * @param integer				$old_ausbildungssemester
	 *
	 * @return stdClass
	 */
	public function checkStatusHistoryLaststatus(
		$prestudent_id,
		$status_kurzbz,
		$new_date,
		$new_studiensemester_kurzbz,
		$new_ausbildungssemester,
		$old_studiensemester_kurzbz,
		$old_ausbildungssemester
	) {
		$result = $this->prepareStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_date,
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		);

		if (isError($result))
			return $result;

		return success(getData($result)['laststatus']);
	}

	/**
	 * Checks if two consecutively Unterbrecher have the same
	 * ausbildungssemester in the status history.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param DateTime				$new_date
	 * @param string				$new_studiensemester_kurzbz
	 * @param integer				$new_ausbildungssemester
	 * @param string				$old_studiensemester_kurzbz
	 * @param integer				$old_ausbildungssemester
	 *
	 * @return stdClass
	 */
	public function checkStatusHistoryUnterbrechersemester(
		$prestudent_id,
		$status_kurzbz,
		$new_date,
		$new_studiensemester_kurzbz,
		$new_ausbildungssemester,
		$old_studiensemester_kurzbz,
		$old_ausbildungssemester
	) {
		$result = $this->prepareStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_date,
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		);

		if (isError($result))
			return $result;

		return success(getData($result)['unterbrechersemester']);
	}

	/**
	 * Checks if an Unterbrecher followed by an Abbrecher have the same
	 * ausbildungssemester in the status history.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param DateTime				$new_date
	 * @param string				$new_studiensemester_kurzbz
	 * @param integer				$new_ausbildungssemester
	 * @param string				$old_studiensemester_kurzbz
	 * @param integer				$old_ausbildungssemester
	 *
	 * @return stdClass
	 */
	public function checkStatusHistoryAbbrechersemester(
		$prestudent_id,
		$status_kurzbz,
		$new_date,
		$new_studiensemester_kurzbz,
		$new_ausbildungssemester,
		$old_studiensemester_kurzbz,
		$old_ausbildungssemester
	) {
		$result = $this->prepareStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_date,
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		);

		if (isError($result))
			return $result;

		return success(getData($result)['abbrechersemester']);
	}

	/**
	 * Checks if no Diplomant is followed by a Student in the status history.
	 *
	 * @param integer				$prestudent_id
	 * @param string				$status_kurzbz
	 * @param DateTime				$new_date
	 * @param string				$new_studiensemester_kurzbz
	 * @param integer				$new_ausbildungssemester
	 * @param string				$old_studiensemester_kurzbz
	 * @param integer				$old_ausbildungssemester
	 *
	 * @return stdClass
	 */
	public function checkStatusHistoryDiplomant(
		$prestudent_id,
		$status_kurzbz,
		$new_date,
		$new_studiensemester_kurzbz,
		$new_ausbildungssemester,
		$old_studiensemester_kurzbz,
		$old_ausbildungssemester
	) {
		$result = $this->prepareStatusHistory(
			$prestudent_id,
			$status_kurzbz,
			$new_date,
			$new_studiensemester_kurzbz,
			$new_ausbildungssemester,
			$old_studiensemester_kurzbz,
			$old_ausbildungssemester
		);

		if (isError($result))
			return $result;

		return success(getData($result)['diplomant']);
	}

	/**
	 * Checks if Personenkennzeichen is set correctly.
	 *
	 * @param integer				$prestudent_id
	 *
	 * @return stdClass
	 */
	public function checkPersonenkennzeichen($prestudent_id)
	{
		$this->_ci->PrestudentstatusModel->addSelect('tbl_prestudentstatus.prestudent_id');
		$this->_ci->PrestudentstatusModel->addSelect('tbl_student.matrikelnr');

		$this->_ci->PrestudentstatusModel->addJoin('public.tbl_student', 'prestudent_id');

		$this->_ci->PrestudentstatusModel->addOrder('tbl_prestudentstatus.datum', 'DESC');
		$this->_ci->PrestudentstatusModel->addOrder('tbl_prestudentstatus.insertamum', 'DESC');
		$this->_ci->PrestudentstatusModel->addOrder('tbl_prestudentstatus.ext_id', 'DESC');
		
		$this->_ci->PrestudentstatusModel->addLimit(1);
		
		$result = $this->_ci->PrestudentstatusModel->loadWhere([
			'tbl_prestudentstatus.status_kurzbz' => self::STATUS_STUDENT
		]);

		if (isError($result))
			return $result;

		if (!hasData($result))
			return success(true); // Not a student yet so no wrong personenkennzeichen

		$data = current(getData($result));

		$jahr = $this->_ci->StudiensemesterModel->getStudienjahrNumberFromStudiensemester($data->studiensemester_kurzbz);


		return success($jahr == mb_substr($data->matrikelnr, 0, 2));
	}

	// TODO(chris): check status history error_bewerberOrgformUngleichStudentOrgform

	/**
	 * Check if History of StatusData is valid
	 * @param integer $prestudent_id
	 * @return error if not valid, array StatusArr if valid
	 */
	private function _checkIfValidStatusHistory(
		$prestudent_id,
		$status_kurzbz,
		$new_status_studiensemester_kurzbz,
		$new_status_datum,
		$new_status_ausbildungssemester,
		$new_studienplan_id,
		$old_status_studiensemester = null,
		$old_status_ausbildungssemester = null
	) {
		//get start studiensemester
		$semResult = $this->_ci->StudiensemesterModel->load([
			'studiensemester_kurzbz' => $new_status_studiensemester_kurzbz
		]);

		if (isError($semResult))
		{
			$this->output->set_status_header(REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			return $this->outputJson(getError($semResult));
		}

		if (!hasData($semResult)) {
			return error($this->_ci->p->t('lehre', 'error_noStudiensemester') . $new_status_studiensemester_kurzbz);
		}

		$studiensemester = getData($semResult)[0];
		$new_status_semesterstart = $studiensemester->start;

		// get studienplan orgform
		$new_studienplan_orgform_kurzbz = '';
		$this->_ci->StudienplanModel->addSelect('orgform_kurzbz');
		$stplResult = $this->_ci->StudienplanModel->load([
			'studienplan_id' => $new_studienplan_id
		]);

		if (isError($stplResult))
		{
			$this->output->set_status_header(REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			return $this->outputJson(getError($stplResult));
		}

		if (hasData($stplResult)) $new_studienplan_orgform_kurzbz = getData($stplResult)[0]->orgform_kurzbz;


		//get all prestudentstati
		$resultPs = $this->_ci->PrestudentstatusModel->getAllPrestudentstatiWithStudiensemester($prestudent_id);

		if (isError($resultPs)) return $resultPs;

		$resultArr = hasData($resultPs) ? getData($resultPs) : [];
		$statusArr = [];

		$newStatusInserted = false;
		$new_status_datum_form = new DateTime($new_status_datum);
		$new_status_semesterstart_form = new DateTime($new_status_semesterstart);

		if (!isEmptyArray($resultArr))
		{
			// neuen Status zum Hinzufügen
			$first_status = $resultArr[0];
			$neuer_status = new stdClass();
			$neuer_status->status_kurzbz = $status_kurzbz;
			$neuer_status->studiensemester_kurzbz = $new_status_studiensemester_kurzbz;
			$neuer_status->datum = $new_status_datum;
			$neuer_status->ausbildungssemester = $new_status_ausbildungssemester;
			$neuer_status->studienplan_orgform_kurzbz = $new_studienplan_orgform_kurzbz;
			$neuer_status->matrikelnr = $first_status->matrikelnr;
			$neuer_status->vorname = $first_status->vorname;
			$neuer_status->nachname = $first_status->nachname;

			// Status, welcher gerade geändert wird, holen
			$status_to_change = array_filter(
				$resultArr,
				function ($status) use ($status_kurzbz, $old_status_studiensemester, $old_status_ausbildungssemester) {
					return
						$status->status_kurzbz == $status_kurzbz
						&& $status->studiensemester_kurzbz == $old_status_studiensemester
						&& $status->ausbildungssemester == $old_status_ausbildungssemester;
				}
			);

			if (!isEmptyArray($status_to_change))
			{
				$status_to_change_index = key($status_to_change);

				// wenn sich Studiensemester und Ausbildungssemester nicht geändert haben...
				if ($new_status_studiensemester_kurzbz == $old_status_studiensemester
					&& $new_status_ausbildungssemester == $old_status_ausbildungssemester)
				{
					// ...neuen status an selber stelle einfügen wie zu ändernder Status
					$resultArr[$status_to_change_index] = (object) array_merge((array) $resultArr[$status_to_change_index], (array) $neuer_status);
					$newStatusInserted = true;
				}
				else
				{
					// bei Status mit neuem Semester: alten Status entfernen
					unset($resultArr[$status_to_change_index]);
				}
			}
		}

		foreach ($resultArr as $row)
		{
			$studiensemester_start = new DateTime($row->studiensemester_start);
			$status_datum = new DateTime($row->datum);

			if ($new_status_datum_form >= $status_datum && $new_status_semesterstart_form >= $studiensemester_start)
			{
				if (!$newStatusInserted)
				{
					// neuer Status erstmals größer als Datum eines bestehenden Status -> neuen Status EINMALIG einfügen für spätere Statusprüfung
					$statusArr[] = $neuer_status;
					$newStatusInserted = true;
				}
				$statusArr[] = $row;
			}
			elseif ($new_status_datum_form <= $status_datum && $new_status_semesterstart_form <= $studiensemester_start)
			{
				$statusArr[] = $row;
			}
			else
			{
				// Zeitabfolge ungültig, Fehler
				return error($this->_ci->p->t('lehre', 'error_statuseintrag_zeitabfolge'));
			}
		}

		// erster Studentstatus
		$ersterStudent = null;

		// Über alle gespeicherten Status gehen und Statusabfolge prüfen
		for ($i = 0; $i < count($statusArr); $i++)
		{
			$curr_status = $statusArr[$i];
			$curr_status_kurzbz = $curr_status->status_kurzbz;
			$curr_status_ausbildungssemester = $curr_status->ausbildungssemester;
			$next_idx = $i - 1; //absteigend sortiert, nächster Status ist vorheriger Eintrag
			$next_status = isset($statusArr[$next_idx]) ? $statusArr[$next_idx] : null;

			$studentName = $curr_status->vorname . ' ' . $curr_status->nachname;

			if ($curr_status_kurzbz == self::STUDENT_STATUS) $ersterStudent = $curr_status;

			// Abbrecher- oder Absolventenstatus muss Endstatus sein
			if (isset($next_status) && in_array($curr_status_kurzbz, $this->_endStatusArr))
			{
				return error($studentName . ' ' . $this->_ci->p->t('lehre', 'error_endstatus'));
			}

			// wenn Unterbrecher auf Unterbrecher folgt, muss Ausbildungssemester gleich sein
			if
				($curr_status_kurzbz == self::UNTERBRECHER_STATUS && isset($next_status) && $next_status->status_kurzbz == self::UNTERBRECHER_STATUS
				&& $curr_status_ausbildungssemester != $next_status->ausbildungssemester)
			{
				return error($studentName . ' ' . $this->_ci->p->t('lehre', 'error_consecutiveUnterbrecher'));
			}

			// wenn Abbrecher auf Unterbrecher folgt, muss Ausbildungssemester gleich sein
			if (isset($next_status)
				&& $curr_status_kurzbz == self::UNTERBRECHER_STATUS
				&& $next_status->status_kurzbz == self::ABBRECHER_STATUS && $curr_status_ausbildungssemester != $next_status->ausbildungssemester)
			{
				return error($studentName . ' ' . $this->_ci->p->t('lehre', 'error_consecutiveUnterbrecherAbbrecher'));
			}

			if (isset($next_status) && $next_status->status_kurzbz == self::STUDENT_STATUS)
			{
				$restliche_status_obj = array_slice($statusArr, $i);
				$restliche_status = array_unique(array_column($restliche_status_obj, 'status_kurzbz'));
				$status_intersected = array_intersect($restliche_status, $this->_statusAbfolgeVorStudent);

				// Vor Studentstatus darf kein Diplomand Status vorhanden sein
				if (in_array(self::DIPLOMAND_STATUS, $restliche_status))
				{
					return error($studentName . ' ' . $this->_ci->p->t('lehre', 'error_consecutiveDiplomandStudent'));
				}

				// Vor Studentstatus müssen bestimmte Status vorhanden sein
				if (array_values($status_intersected) != array_values(array_reverse($this->_statusAbfolgeVorStudent)))
				{
					return error(
						$studentName . ' '
						. $this->_ci->p->t('lehre', 'error_wrongStatusOrderBeforeStudent', array(implode(', ', $this->_statusAbfolgeVorStudent)))
					);
				}
			}
		}

		if (isset($ersterStudent))
		{
			$studentName = $ersterStudent->vorname . ' ' . $ersterStudent->nachname;

			// wenn erster Studentstatus, checken ob Personenkennzeichen passt
			$studienjahrNumber = $this->_ci->StudiensemesterModel->getStudienjahrNumberFromStudiensemester($ersterStudent->studiensemester_kurzbz);

			if ($studienjahrNumber != mb_substr($ersterStudent->matrikelnr, 0, 2))
			{
				return error($studentName . ' ' . $this->_ci->p->t('lehre', 'error_personenkennzeichenPasstNichtZuStudiensemester'));
			}

			// wenn erster Studentstatus, checken ob Orgform des Bewerbers mit Studenten übereinstimmt
			if (!isEmptyArray(
					array_filter(
						$restliche_status_obj,
						function ($s) use ($ersterStudent) {
							return
								$s->status_kurzbz == self::BEWERBER_STATUS
								&& (
									$s->studienplan_orgform_kurzbz != $ersterStudent->studienplan_orgform_kurzbz
								);
						}
					)
				)
			)
			{
				return error($studentName . ' ' . $this->_ci->p->t('lehre', 'error_bewerberOrgformUngleichStudentOrgform'));
			}
		}

		return $resultPs;
	}




	/**
	 * Provides Application Data
	 * @param integer $prestudent_id
	 * @return error if not valid, array with ApplicationData if valid
	 */
	private function _getApplicationData($prestudent_id)
	{
		$this->_ci->PrestudentModel->addJoin('public.tbl_person p', 'ON (p.person_id = public.tbl_prestudent.person_id)');

		$result = $this->_ci->PrestudentModel->load([
			'prestudent_id'=> $prestudent_id,
		]);
		if(isError($result))
		{
			return getData($result);
		}

		return $result;
	}

}
