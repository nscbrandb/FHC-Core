<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 *
 */
class ProfilUpdate extends Auth_Controller
{

	public function __construct(){
		parent::__construct([
			'index' => ['student/anrechnung_beantragen:r', 'user:r'], // TODO(chris): permissions?
			'getAllRequests' => ['student/anrechnung_beantragen:r', 'user:r'],
			'acceptProfilRequest'=>['user:r'],
			'denyProfilRequest'=>['user:r'],

		]);
		

		$this->load->model('person/Profil_change_model','ProfilChangeModel');
		$this->load->model('person/Kontakt_model','KontaktModel');
		$this->load->model('person/Adresse_model','AdresseModel');
		$this->load->model('person/Adressentyp_model', 'AdressenTypModel');
		$this->load->model('person/Person_model','PersonModel');
	}


	public function index(){
		$this->load->view('Cis/ProfilUpdate');
	}

	public function getAllRequests(){
		$res = $this->ProfilChangeModel->getProfilUpdate();
		$res = hasData($res)? getData($res) : null;
		echo json_encode($res);
	}

	public function acceptProfilRequest(){

		$_POST = json_decode($this->input->raw_input_stream,true);
		$id = $this->input->post('profil_update_id',true);
		$uid = $this->input->post('uid',true);	

		//? fetching person_id using UID
		$personID = $this->PersonModel->getByUid($uid);
		$personID = hasData($personID)? getData($personID)[0]->person_id : null;
		$status_message = $this->input->post('status_message',true);
		$topic = $this->input->post('topic',true);

		//! somehow the xss check converted boolean false to empty string
		$requested_change = $this->input->post('requested_change');
		
		//! check for required information
		if(!isset($id) || !isset($uid) || !isset($personID) || !isset($requested_change) || !isset($topic)){
			return json_encode(error("missing required information"));
		}
		
		if(is_array($requested_change) && array_key_exists("adresse_id",$requested_change)){
			$resID = $this->handleAdresse($requested_change, $personID);
			$resID = hasData($resID) ? getData($resID) : null;
			$requested_change['adresse_id'] = $resID;
			
		}else if (is_array($requested_change) && array_key_exists("kontakt_id", $requested_change)){
			$resID = $this->handleKontakt($requested_change, $personID);
			$resID = hasData($resID) ? getData($resID)[0] : null;
			$requested_change['kontakt_id'] = $resID;
			
		}else{
			switch($topic){
				case "titel": $topic ="titelpre"; break;
				case "postnomen": $topic = "titelpost"; break;
			}
			$result = $this->PersonModel->update($personID,[$topic=>$requested_change]);
			if(isError($result)){
				echo json_encode($result);
				return;
			}
		}
		
		echo json_encode($this->setStatusOnUpdateRequest($id, "accepted", $status_message, $requested_change));
	}

	public function denyProfilRequest(){
		
		$_POST = json_decode($this->input->raw_input_stream,true);
		$id = $this->input->post('profil_update_id',true);
		$status_message = $this->input->post('status_message',true);
		
		echo json_encode($this->setStatusOnUpdateRequest($id, "rejected", $status_message));
	}

	private function setStatusOnUpdateRequest($id, $status, $status_message, $requested_change=NULL){
		$update = ["status"=>$status,"status_timestamp"=>"NOW()","status_message"=>$status_message];
		if(isset($requested_change)) { $update['requested_change'] = $requested_change; } 
		return $this->ProfilChangeModel->update([$id], $update);
	}


	private function handleKontakt($requested_change, $personID){
		$kontakt_id = $requested_change["kontakt_id"];
		//? removes the kontakt_id because we don't want to update the kontakt_id in the database
		unset($requested_change["kontakt_id"]);
		

		//! ADD
		if(array_key_exists('add',$requested_change) && $requested_change['add']){
			//? removes add flag
			unset($requested_change['add']);
			//? fields like insertvon are not filled when inserting new row
			$requested_change['person_id'] = $personID;
			$res = $this->KontaktModel->insert($requested_change);
		}
		//! DELETE
		elseif(array_key_exists('delete',$requested_change) && $requested_change['delete']){
			$res = $this->KontaktModel->delete($kontakt_id);
		}
		//! UPDATE
		else{
			$res = $this->KontaktModel->update($kontakt_id,$requested_change);
		}
		return $res;
	}

	private function handleAdresse($requested_change, $personID){

		$this->AdressenTypModel->addSelect(["adressentyp_kurzbz"]);
		$adr_kurzbz = $this->AdressenTypModel->loadWhere(["bezeichnung"=>$requested_change['typ']]);
		$adr_kurzbz = hasData($adr_kurzbz)? getData($adr_kurzbz)[0]->adressentyp_kurzbz : null;
		//? replace the address_typ with its correct kurzbz foreign key
		$requested_change['typ']= $adr_kurzbz;
		
		$adresse_id = $requested_change["adresse_id"];
		//? removes the adresse_id because we don't want to update the kontakt_id in the database
		unset($requested_change["adresse_id"]);

		
		//! ADD
		if(array_key_exists('add',$requested_change) && $requested_change['add']){
			//? removes add flag
			unset($requested_change['add']);
			//? fields like insertvon are not filled when inserting new row
			$requested_change['person_id'] = $personID;
			//TODO: zustelladresse, heimatadresse, rechnungsadresse und nation werden nicht beachtet
			$res = $this->AdresseModel->insert($requested_change);
		}
		//! DELETE
		elseif(array_key_exists('delete',$requested_change) && $requested_change['delete']){
			$res = $this->AdresseModel->delete($adresse_id);
		}
		//! UPDATE
		else{
			$res = $this->AdresseModel->update($adresse_id,$requested_change);
		}
		return $res;
	}
}