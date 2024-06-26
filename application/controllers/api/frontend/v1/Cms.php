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

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This controller operates between (interface) the JS (GUI) and the SearchBarLib (back-end)
 * Provides data to the ajax get calls about the searchbar component
 * This controller works with JSON calls on the HTTP GET and the output is always JSON
 */
class Cms extends FHCAPI_Controller
{
	
	/**
	 * Object initialization
	 */
	public function __construct()
	{
		// NOTE(chris): additional permission checks will be done in SearchBarLib
		parent::__construct([
			'ContentID' => self::PERM_LOGGED,
			'getOrtKurzbzContent' => self::PERM_LOGGED,
            'content' => self::PERM_LOGGED,
			'news' => self::PERM_LOGGED,
		]);

		$this->load->library('CmsLib');

	}

	//------------------------------------------------------------------------------------------------------------------
	// Private methods

	private function fetchContent($content_id){
		$content = $this->cmslib->getContent($content_id);

		if (isError($content))
            $this->terminateWithError(getError($content), self::ERROR_TYPE_GENERAL);

		if(getData($content)){
			return getData($content);
		}else{
			$this->terminateWithError("No content was found", self::ERROR_TYPE_GENERAL);
		}
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

    /**
	 * fetches the content with the content_id and additional parameters
	 */
    public function content()
	{
        // getting the get parameters
        $content_id = $this->input->get("content_id",TRUE);
        $version = $this->input->get("version",TRUE);
        $sprache = $this->input->get("sprache",TRUE);
        $sichtbar = $this->input->get("sichtbar",TRUE);

        // return early if the content_id is missing
		if(!isset($content_id))
			$this->terminateWithError("content_id is missing", self::ERROR_TYPE_GENERAL);

		$content = $this->fetchContent($content_id); 

		$this->terminateWithSuccess(getData($content));
	}

	/**
	 * Gets a JSON body via HTTP POST and provides the parameters
	 */
	public function ContentID()
	{
		// if error
		//$this->terminateWithError(SearchBarLib::ERROR_WRONG_JSON, self::ERROR_TYPE_GENERAL);
		
		$ort_kurzbz = $this->input->get('ort_kurzbz',TRUE);
		
		if(!$ort_kurzbz){
			$this->terminateWithError("missing ort_kurzbz parameter", self::ERROR_TYPE_GENERAL);
		}

		$result = $this->OrtModel->getContentID($ort_kurzbz);
		
		if(isError($result)){
			$this->terminateWithError(getError($result), self::ERROR_TYPE_GENERAL);
		}

		$result = hasData($result) ? current(getData($result)) : null;
		
		$this->terminateWithSuccess($result->content_id ?? NULL);
	}

	public function news()
	{
		$this->load->model('content/news_model', 'NewsModel');
		
		$limit =  $this->input->get('limit',TRUE);
		
		// return early if the limit parameter is missing or is not greater than 0
		if(!isset($limit) || $limit < 1)
		$this->terminateWithError("API parameters are missing", self::ERROR_TYPE_GENERAL);

		//query the news
		$news = $this->NewsModel->getAll($limit);

		//get the data or terminate with error
		$news = $this->getDataOrTerminateWithError($news);

		// check if any news are there
		if($news === NULL){
			$this->terminateWithError("No news content has been found");
		}

		// collect the content of the news
		foreach($news as $news_element){
			$this->addMeta("asdfs",$news_element->content_id);
			$news_element->content_obj = $this->fetchContent($news_element->content_id);
		}

		$this->terminateWithSuccess($news);
        
	}

	
}

