<?php

/**
 * PDO principal backend
 *
 * This is a simple principal backend that maps exactly to the users table, as 
 * used by Sabre_DAV_Auth_Backend_PDO.
 *
 * It assumes all principals are in a single collection. The default collection 
 * is 'principals/', but this can be overriden.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class MySabre_DAVACL_PrincipalBackend implements Sabre_DAVACL_IPrincipalBackend 
{
    /**
     * PDO table name for 'principals' 
     * 
     * @var string 
     */
    protected $tableName;

    /**
     * PDO table name for 'group members' 
     * 
     * @var string 
     */
    protected $groupMembersTableName;

    /**
     * Sets up the backend.
     * 
     * @param PDO $pdo
     * @param string $tableName 
     */
    public function __construct($auth)
	{
		$this->auth = $auth;
    } 

	/**
	 * Liefert den eingeloggten User
	 */
	function getUser()
	{
		return $this->auth->getCurrentUser();
	}


    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only 
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can 
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname 
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV 
     *     field that's actualy injected in a number of other properties. If
     *     you have an email address, use this property.
     * 
     * @param string $prefixPath 
     * @return array 
     */
    public function getPrincipalsByPrefix($prefixPath) 
	{
		//$prefixPath = principals
		//error_log('Principal.php/getPrincipalsByPrefix('.$prefixPath.')');
        $principals = array();
		$user = $this->getUser();
		if($prefixPath=='principals')
		{
		    $principals[] = array(
					'id' => 1,
		            'uri' => 'principals/'.$user,
		            '{DAV:}displayname' => $user,
		            '{http://sabredav.org/ns}email-address' => $user.'@example.com',
		        );
		}
		elseif($prefixPath=='principals/oesi')
		{
		    $principals[] = array(
					'id' => 2,
		            'uri' => 'principals/'.$user.'/calendar-proxy-read',
		            '{DAV:}displayname' => '',
		            '{http://sabredav.org/ns}email-address' => '',
		        );
		    $principals[] = array(
					'id' => 3,
		            'uri' => 'principals/'.$user.'/calendar-proxy-write',
		            '{DAV:}displayname' => '',
		            '{http://sabredav.org/ns}email-address' => '',
		        );
		}

        return $principals;

    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from 
     * getPrincipalsByPrefix. 
     * 
     * @param string $path 
     * @return array 
     */
    public function getPrincipalByPath($path) 
	{
		//$path = principals/oesi
		//error_log('Principal.php/getPrincipalByPath('.$path.')');
		$user = $this->getUser();
        $result = array(
            'id'  => $user,
            'uri' => 'principals/'.$user,
            '{DAV:}displayname' => $user,
            '{http://sabredav.org/ns}email-address' => $user.'@example.com',
        );

		//error_log("data:".print_r($result,true));
		return $result;

    }

    /**
     * Returns the list of members for a group-principal 
     * 
     * @param string $principal 
     * @return array 
     */
    public function getGroupMemberSet($principal) 
	{
		//error_log('Principal.php/getGroupMemberSet('.$principal.')');
        $result = array();
        return $result;
    
    }

    /**
     * Returns the list of groups a principal is a member of 
     * 
     * @param string $principal 
     * @return array 
     */
    public function getGroupMembership($principal) 
	{
		//$principal = username
		$result = array();
        return $result;

    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's. 
     * 
     * @param string $principal 
     * @param array $members 
     * @return void
     */
    public function setGroupMemberSet($principal, array $members) 
	{
		throw new Sabre_DAV_Exception('Not implemented');
    }

}
