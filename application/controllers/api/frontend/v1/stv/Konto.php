<?php
/**
 * Copyright (C) 2024 fhcomplete.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

use CI3_Events as Events;

/**
 * This controller operates between (interface) the JS (GUI) and the back-end
 * Provides data to the ajax get calls about a Konto
 * Listens to ajax post calls to change the Konto data
 * This controller works with JSON calls on the HTTP GET or POST and the output is always JSON
 */
class Konto extends FHCAPI_Controller
{
	/**
	 * Calls the parent's constructor and prepares libraries and phrases
	 */
	public function __construct()
	{
		// TODO(chris): permissions
		parent::__construct([
			'get' => 'student/stammdaten:r',
			'getBuchungstypen' => 'student/stammdaten:r', // alle?
			'checkDoubles' => ['admin:r', 'assistenz:r'],
			'insert' => ['admin:w', 'assistenz:w'],
			'update' => ['admin:w', 'assistenz:w'],
			'delete' => ['admin:w', 'assistenz:w']
		]);

		// Load models
		$this->load->model('crm/Konto_model', 'KontoModel');

		// Load language phrases
		$this->loadPhrases([
			'ui'
		]);
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Get details for a prestudent
	 *
	 * @return void
	 */
	public function get()
	{
		$this->load->library('form_validation');

		$person_id = $this->input->post('person_id');
		if (!$person_id || !is_array($person_id)) {
			$this->form_validation->set_rules('person_id', 'Person ID', 'required');

			if (!$this->form_validation->run())
				$this->terminateWithValidationErrors($this->form_validation->error_array());
		}


		$studiengang_kz = $this->input->post('studiengang_kz');

		if ($this->input->post('only_open')) {
			$result = $this->KontoModel->getOffeneBuchungen($person_id, $studiengang_kz);
		} else {
			$result = $this->KontoModel->getAlleBuchungen($person_id, $studiengang_kz);
		}

		#$result = $this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);
		$result = $result->retval;

		// sort into tree
		$childs = [];
		$data = [];
		foreach ($result as $entry) {
			if ($entry->buchungsnr_verweis) {
				if (isset($data[$entry->buchungsnr_verweis])) {
					if (!isset($data[$entry->buchungsnr_verweis]->_children))
						$data[$entry->buchungsnr_verweis]->_children = [];
					$data[$entry->buchungsnr_verweis]->_children[] = $entry;
				} else {
					if (!isset($childs[$entry->buchungsnr_verweis]))
						$childs[$entry->buchungsnr_verweis] = [];
					$childs[$entry->buchungsnr_verweis][] = $entry;
				}
			} else {
				$data[$entry->buchungsnr] = $entry;
				if (isset($childs[$entry->buchungsnr]))
					$entry->_children = $childs[$entry->buchungsnr];
			}
		}

		$this->terminateWithSuccess(array_values($data));
	}

	/**
	 * Get list of Buchungstypen
	 *
	 * @return void
	 */
	public function getBuchungstypen()
	{
		$this->load->model('crm/Buchungstyp_model', 'BuchungstypModel');

		$result = $this->BuchungstypModel->load();

		#$data = $this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);
		$data = $result->retval;

		$this->terminateWithSuccess($data);
	}

	/**
	 * Check double Buchungen
	 *
	 * @return void
	 */
	public function checkDoubles()
	{
		if (!defined('FAS_DOPPELTE_BUCHUNGSTYPEN_CHECK') || !FAS_DOPPELTE_BUCHUNGSTYPEN_CHECK)
			$this->terminateWithSuccess(false);

		$this->load->library('form_validation');

		$person_ids = $this->input->post('person_id');

		if (!$person_ids || !is_array($person_ids)) {
			$person_ids = [$person_ids];
			$this->form_validation->set_rules('person_id', 'Person ID', 'required');
		}
		$this->form_validation->set_rules('studiensemester_kurzbz', 'Studiensemester', 'required');
		$this->form_validation->set_rules('buchungstyp_kurzbz', 'Buchungstyp', 'required');

		if (!$this->form_validation->run())
			$this->terminateWithValidationErrors($this->form_validation->error_array());


		$buchungstypen = unserialize(FAS_DOPPELTE_BUCHUNGSTYPEN_CHECK);
		$buchung = $this->input->post('buchungstyp_kurzbz');
		
		if (!isset($buchungstypen[$buchung]))
			$this->terminateWithSuccess(false);

		$result = $this->KontoModel->checkDoubleBuchung($person_ids, $this->input->post('studiensemester_kurzbz'), $buchungstypen[$buchung]);

		#$result = $this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);
		$result = $result->retval;

		if (!$result)
			$this->terminateWithSuccess(false);

		$persons = array_map(function ($row) {
			return $row->nachname . ' ' . $row->vorname;
		}, $result);
		
		// TODO(chris): Phrases
		$result = $this->p->t('konto', 'buchung_vorhanden') . "\n";
		if (count($persons) > 10) {
			$result .= "-" . implode("\n-", array_slice($persons, 0, 10)) . "\n";
			
			if (count($persons) == 11) {
				$result .= "\n" . $this->p->t('konto', 'and_1_additional_person');
			} else {
				$result .= "\n" . $this->p->t('konto', 'and_x_additional_person', [
					'x' => count($persons) - 10
				]);
			}
		} else {
			$result .= "-" . implode("\n-", $persons) . "\n";
		}
		$result .= $this->p->t('konto', 'proceed');

		$this->addError($result, 'confirm');

		$this->terminateWithSuccess(true);
	}


	/**
	 * Save Buchung
	 *
	 * @return void
	 */
	public function insert()
	{
		$this->load->library('form_validation');

		$person_ids = $this->input->post('person_id');

		if (!$person_ids || !is_array($person_ids)) {
			$person_ids = [$person_ids];
			$this->form_validation->set_rules('person_id', 'Person ID', 'required');
		}
		$this->form_validation->set_rules('betrag', 'Betrag', 'numeric');
		$this->form_validation->set_rules('buchungsdatum', 'Buchungsdatum', 'is_valid_date');
		$this->form_validation->set_rules('buchungstext', 'Buchungstext', 'max_length[256]');
		$this->form_validation->set_rules('mahnspanne', 'Mahnspanne', 'integer');
		$this->form_validation->set_rules('buchungstyp_kurzbz', 'Buchungstyp', 'required|max_length[32]');
		$this->form_validation->set_rules('studiensemester_kurzbz', 'Studiensemester', 'required|max_length[16]');
		$this->form_validation->set_rules('studiengang_kz', 'Studiengang', 'required|has_permissions_for_stg[admin:rw,assistenz:rw]');
		$this->form_validation->set_rules('credit_points', 'Credit Points', 'numeric');

		Events::trigger('konto_insert_validation', $this->form_validation);

		if (!$this->form_validation->run())
			$this->terminateWithValidationErrors($this->form_validation->error_array());


		$allowed = [
			'betrag',
			'buchungsdatum',
			'buchungstext',
			'mahnspanne',
			'buchungstyp_kurzbz',
			'studiensemester_kurzbz',
			'studiengang_kz',
			'credit_points',
			'anmerkung'
		];
		$data = [
			'updateamum' => date('c'),
			'updatevon' => getAuthUID()
		];
		foreach ($allowed as $field)
			if ($this->input->post($field) !== null)
				$data[$field] = $this->input->post($field);

		if (defined('FAS_BUCHUNGSTYP_FIXE_KOSTENSTELLE') && isset(unserialize(FAS_BUCHUNGSTYP_FIXE_KOSTENSTELLE)[$data['buchungstyp_kurzbz']])) {
			$data['kostenstelle'] = unserialize(FAS_BUCHUNGSTYP_FIXE_KOSTENSTELLE)[$data['buchungstyp_kurzbz']];
		}

		$result = [];
		foreach ($person_ids as $person_id) {
			$id = $this->KontoModel->insert(array_merge($data, ['person_id' => $person_id]));
			if (isError($id)) {
				$this->addError(getError($id), self::ERROR_TYPE_GENERAL);
			} else {
				$data = $this->KontoModel->withAdditionalInfo()->load(getData($id));
				if (isError($data))
					$this->addError(getError($data), self::ERROR_TYPE_GENERAL);
				else
					$result[] = current(getData($data));
			}
		}

		if ($result)
			$this->terminateWithSuccess($result);
		// NOTE(chris): else there should already be error in the return object
	}

	/**
	 * Save Buchung
	 *
	 * @return void
	 */
	public function update()
	{
		$this->load->library('form_validation');

		$this->form_validation->set_rules('buchungsnr', 'Buchungsnr', 'required');
		$this->form_validation->set_rules('betrag', 'Betrag', 'numeric');
		$this->form_validation->set_rules('buchungsdatum', 'Buchungsdatum', 'is_valid_date');
		$this->form_validation->set_rules('buchungstext', 'Buchungstext', 'max_length[256]');
		$this->form_validation->set_rules('mahnspanne', 'Mahnspanne', 'integer');
		$this->form_validation->set_rules('buchungstyp_kurzbz', 'Buchungstyp', 'required|max_length[32]');
		$this->form_validation->set_rules('studiensemester_kurzbz', 'Studiensemester', 'required|max_length[16]');
		$this->form_validation->set_rules('studiengang_kz', 'Studiengang', 'required|has_permissions_for_stg[admin:rw,assistenz:rw]');
		$this->form_validation->set_rules('credit_points', 'Credit Points', 'numeric');

		Events::trigger('konto_update_validation', $this->form_validation);

		if (!$this->form_validation->run())
			$this->terminateWithValidationErrors($this->form_validation->error_array());


		$id = $this->input->post('buchungsnr');
		$allowed = [
			'betrag',
			'buchungsdatum',
			'buchungstext',
			'mahnspanne',
			'buchungstyp_kurzbz',
			'studiensemester_kurzbz',
			'studiengang_kz',
			'credit_points',
			'anmerkung'
		];
		$data = [
			'updateamum' => date('c'),
			'updatevon' => getAuthUID()
		];
		foreach ($allowed as $field)
			if ($this->input->post($field) !== null)
				$data[$field] = $this->input->post($field);

		$result = $this->KontoModel->update($id, $data);

		#$this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);

		$result = $this->KontoModel->withAdditionalInfo()->load($id);
		
		#$result = $this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);
		$result = $result->retval;

		$this->terminateWithSuccess($result);
	}

	/**
	 * Delete Buchung
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->load->library('form_validation');

		$this->form_validation->set_rules('buchungsnr', 'Buchungsnr', 'required');

		if (!$this->form_validation->run())
			$this->terminateWithValidationErrors($this->form_validation->error_array());


		$buchungsnr = $this->input->post('buchungsnr');

		$result = $this->KontoModel->load($buchungsnr);
		#$result = $this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);
		$result = $result->retval;

		if (!$result)
			$this->terminateWithError('buchung not found', self::ERROR_TYPE_GENERAL);

		$_POST['studiengang_kz'] = current($result)->studiengang_kz;

		$this->form_validation->set_rules('studiengang_kz', 'Studiengang', 'has_permissions_for_stg[admin:rw,assistenz:rw]');

		Events::trigger('konto_delete_validation', $this->form_validation);

		if (!$this->form_validation->run())
			$this->terminateWithValidationErrors($this->form_validation->error_array());


		Events::trigger('konto_delete', $buchungsnr);
		
		$result = $this->KontoModel->delete($buchungsnr);
		#$this->getDataOrTerminateWithError($result);
		if (isError($result))
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);

		$this->terminateWithSuccess();
	}
}
