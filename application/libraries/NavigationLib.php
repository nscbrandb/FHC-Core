<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NavigationWidget logic
 */
class NavigationLib
{
	// Session parameters names
	const SESSION_NAME = 'FHC_NAVIGATION_WIDGET'; // Navigation session name
	const SESSION_MENU_NAME = 'navigation_menu';
	const SESSION_HEADER_NAME = 'navigation_header';

	// Configuration names
	const CONFIG_MENU_NAME = 'navigation_menu';
	const CONFIG_HEADER_NAME = 'navigation_header';
	const CONFIG_NAVIGATION_FILENAME = 'navigation.php';

	const NAVIGATION_PAGE_PARAM = 'navigation_page'; // Navigation page parameter name

	private $_ci; // Code igniter instance
	private $_navigationPage; // unique id for this navigation widget

	/**
	 * Gets the CI instance and loads message helper
	 */
	public function __construct($params = null)
	{
		$this->_ci =& get_instance(); // get code igniter instance

		// Loads navigation configs
		$this->_ci->config->load('navigation');

		// Loads helper message to manage returning messages
		$this->_ci->load->helper('message');
		// Loads helper session to manage the php session
		$this->_ci->load->helper('session');

		// Loads library ExtensionsLib
		$this->_ci->load->library('ExtensionsLib');

		$this->_navigationPage = $this->_getNavigationtPage($params); // sets the id for the related navigation widget
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Creates the left Menu for each Page
	 * @param navigation_widget_called GET Parameter witch holds the currently called Page
	 * @return array with the Menu Entries
	 */
	public function getMenuArray($navigationPage)
	{
		$menuArray = array();

		if (isset($navigationPage))
		{
			// Get Menu Entries of the Core
			$navigationMenuArray = $this->_ci->config->item(self::CONFIG_MENU_NAME);
			$menuArray = $this->_wildcardsearch($navigationMenuArray, $navigationPage);

			// Load Menu Entries of Extensions
			$extensions = $this->_ci->extensionslib->getInstalledExtensions();
			if(hasData($extensions))
			{
				$json_extension = array();
				foreach($extensions->retval as $ext)
				{
					$filename = APPPATH.'config/'.ExtensionsLib::EXTENSIONS_DIR_NAME.'/'.$ext->name.'/'.self::CONFIG_NAVIGATION_FILENAME;
					if (file_exists($filename))
					{
						unset($config);
						include($filename);
						if(isset($config[self::CONFIG_MENU_NAME]) && is_array($config[self::CONFIG_MENU_NAME]))
						{
							$json_extension = array_merge_recursive(
								$json_extension,
								$this->_wildcardsearch($config[self::CONFIG_MENU_NAME],
								$navigationPage)
							);
						}
					}
				}
				// Merge Extension Menuentries with the Core Entries
				$menuArray = array_merge_recursive($menuArray, $json_extension);
			}

			// Load dynamic Menu Entries from Session
			if (($navigationMenuSessionArray = $this->getSessionMenu()) != null)
			{
				if (isset($navigationMenuSessionArray) && is_array($navigationMenuSessionArray))
				{
					$menuArray = array_merge_recursive($menuArray, $navigationMenuSessionArray);
				}
			}
		}

		return $menuArray;
	}

	/**
	 * Creates the Top Menu for each Page
	 * @param navigation_widget_called GET Parameter witch holds the currently called Page
	 * @return array with the Menu Entries
	 */
	public function getHeaderArray($navigationPage)
	{
		$headerArray = array();

		if (isset($navigationPage))
		{
			// Load Header Entries of Core
			$navigationHeaderArray = $this->_ci->config->item(self::CONFIG_HEADER_NAME);
			$headerArray = $this->_wildcardsearch($navigationHeaderArray, $navigationPage);

			// Load Header Entries of Extensions
			$extensions = $this->_ci->extensionslib->getInstalledExtensions();
			if(hasData($extensions))
			{
				$headerArray_extension = array();
				foreach($extensions->retval as $ext)
				{
					$filename = APPPATH.'config/'.ExtensionsLib::EXTENSIONS_DIR_NAME.'/'.$ext->name.'/'.self::CONFIG_NAVIGATION_FILENAME;
					if (file_exists($filename))
					{
						unset($config);
						include($filename);
						if(isset($config[self::CONFIG_HEADER_NAME]) && is_array($config[self::CONFIG_HEADER_NAME]))
						{
							$headerArray_extension = array_merge_recursive(
								$json_extension,
								$this->_wildcardsearch($config[self::CONFIG_HEADER_NAME],
								$navigationPage)
							);
						}
					}
				}
				$headerArray = array_merge_recursive($headerArray, $headerArray_extension);
			}

			// Load dynamic Header Entries from Session
			if (($navigationHeaderSessionArray = $this->getSessionHeader()) != null)
			{
				if (isset($navigationHeaderSessionArray) && is_array($navigationHeaderSessionArray))
				{
					$headerArray = array_merge_recursive($headerArray, $navigationHeaderSessionArray);
				}
			}
		}

		return $headerArray;
	}

	/**
	 * Returns the structure for one level of the menu
	 */
	public function oneLevel(
		$description, $link = '#', $children = null, $icon = '', $expand = false,
		$subscriptDescription = null, $subscriptLinkClass = null, $subscriptLinkValue = null)
	{
		return array(
			'description' => $description,
			'link' => $link,
			'children'=> $children,
			'icon' => $icon,
			'expand' => $expand,
			'subscriptDescription' => $subscriptDescription,
			'subscriptLinkClass' => $subscriptLinkClass,
			'subscriptLinkValue' => $subscriptLinkValue
		);
	}

	/**
	 * Wrapper method to the session helper funtions to retrive the whole session for this navigation widget
	 */
	public function getSessionMenu()
	{
		$session = getElementSession(self::SESSION_NAME, self::SESSION_MENU_NAME);

		if (isset($session[$this->_navigationPage]))
		{
			return $session[$this->_navigationPage];
		}

		return null;
	}

	/**
	 * Wrapper method to the session helper funtions to retrive the whole session for this navigation widget
	 */
	public function getSessionHeader()
	{
		$session = getElementSession(self::SESSION_NAME, self::SESSION_HEADER_NAME);

		if (isset($session[$this->_navigationPage]))
		{
			return $session[$this->_navigationPage];
		}

		return null;
	}

	/**
	 * Wrapper method to the session helper funtions to retrive one element from the session of this navigation widget
	 */
	public function getElementSessionMenu($name)
	{
		$session = $this->getSessionMenu();

		if (isset($session[$name]))
		{
			return $session[$name];
		}

		return null;
	}

	/**
	 * Wrapper method to the session helper funtions to retrive one element from the session of this navigation widget
	 */
	public function getElementSessionHeader($name)
	{
		$session = $this->getSessionHeader();

		if (isset($session[$name]))
		{
			return $session[$name];
		}

		return null;
	}

	/**
	 * Wrapper method to the session helper funtions to set the whole session for this navigation widget
	 */
	public function setSessionMenu($data)
	{
		setElementSession(self::SESSION_NAME, self::SESSION_MENU_NAME, array($this->_navigationPage => $data));
	}

	/**
	 * Wrapper method to the session helper funtions to set the whole session for this navigation widget
	 */
	public function setSessionHeader($data)
	{
		setElementSession(self::SESSION_NAME, self::SESSION_HEADER_NAME, array($this->_navigationPage => $data));
	}

	/**
	 * Wrapper method to the session helper funtions to set one element in the session for this navigation widget
	 */
	public function setElementSessionMenu($name, $value)
	{
		$session = $this->getSessionMenu();

		if (!isset($session[$this->_navigationPage]))
		{
			$session[$this->_navigationPage] = array();
		}

		$session[$this->_navigationPage][$name] = $value;

		setElementSession(self::SESSION_NAME, self::SESSION_MENU_NAME, $session); // stores the single value
	}

	/**
	 * Wrapper method to the session helper funtions to set one element in the session for this navigation widget
	 */
	public function setElementSessionHeader($name, $value)
	{
		$session = $this->getSessionHeader();

		if (!isset($session[$this->_navigationPage]))
		{
			$session[$this->_navigationPage] = array();
		}

		$session[$this->_navigationPage][$name] = $value;

		setElementSession(self::SESSION_NAME, self::SESSION_HEADER_NAME, $session); // stores the single value
	}

	//------------------------------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Searches a Menuentry. If there is no exact entry it searches for Wildcard Entries with a Star
	 * Example:
	 * Searching for /system/foo/index will Match the following Menuentries:
	 * 		/system/foo/index
	 *		/system/foo/*
	 *		/system/*
	 *		*
	 *
	 * @param $navigationArray Array to Search in.
	 * @param $navigationPage Navigation to search for.
	 * @return Navigation Array if found, empty array otherwise
	 */
	private function _wildcardsearch($navigationArray, $navigationPage)
	{
		// Sort Navigation to have them in correct order
		krsort($navigationArray);

		// 100% match found
		if(isset($navigationArray[$navigationPage]))
		{
			return $navigationArray[$navigationPage];
		}
		else
		{
			foreach($navigationArray as $key=>$row)
			{
				// Search for * Entries
				if(mb_strpos($key, '*') === 0 || mb_strpos($key, '*') === mb_strlen($key) - 1)
				{
					// Take * Entry if Matches
					$search = mb_substr($key, 0, -1);
					if($search == '' || mb_strpos($navigationPage, $search) === 0)
					{
						return $row;
					}
				}
			}
		}

		return array();
	}

	/**
	 * Return an unique string that identify this navigation widget
	 * NOTE: The default value is the URI where the NavigationWidget is called
	 */
	private function _getNavigationtPage($params)
	{
		//
		if ($params != null
			&& is_array($params)
			&& isset($params[self::NAVIGATION_PAGE_PARAM])
			&& !empty(trim($params[self::NAVIGATION_PAGE_PARAM])))
		{
			$navigationPage = $params[self::NAVIGATION_PAGE_PARAM];
		}
		else
		{
			// Gets the current page URI
			$navigationPage = $this->_ci->router->directory.$this->_ci->router->class.'/'.$this->_ci->router->method;
		}

		return $navigationPage;
	}
}
