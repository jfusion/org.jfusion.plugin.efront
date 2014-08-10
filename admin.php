<?php namespace JFusion\Plugins\efront;
/**
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

use JFusion\Factory;
use JFusion\Framework;
use JFusion\Plugin\Plugin_Admin;

use Joomla\Language\Text;

use Psr\Log\LogLevel;

use \Exception;

/**
 * JFusion Admin Class for eFront 3.5+
 * For detailed descriptions on these functions please check Plugin_Admin
 *
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class Admin extends Plugin_Admin
{
	/**
	 * @var $helper Helper
	 */
	var $helper;

    /**
     * @return string
     */
    function getTablename()
    {
        return 'users';
    }

    /**
     * @param string $softwarePath
     *
     * @return array
     */
    function setupFromPath($softwarePath)
    {
	    $myfile = $softwarePath . 'libraries' . DIRECTORY_SEPARATOR . 'configuration.php';

        $params = array();
	    $lines = $this->readFile($myfile);
        if ($lines === false) {
            Framework::raise(LogLevel::WARNING, Text::_('WIZARD_FAILURE') . ': ' . $myfile. ' ' . Text::_('WIZARD_MANUAL'), $this->getJname());
	        return false;
        } else {
            //parse the file line by line to get only the config variables
	        foreach ($lines as $line) {
		        if (strpos($line, 'define') !== false) {
			        if (strpos($line, 'define') == 0){
				        eval($line);
			        }
		        }
	        }


	        $myfile = $softwarePath . 'libraries' . DIRECTORY_SEPARATOR . 'globals.php';

            // this are now predefined in globals.php during efront startup, so let's start with them as well
            define("G_MD5KEY", 'cDWQR#$Rcxsc');
            define ("G_UPLOADPATH", $softwarePath."upload/");


            $lines = $this->readFile($myfile);
            if ($lines === false) {
                Framework::raise(LogLevel::WARNING, Text::_('WIZARD_FAILURE') . ': ' . $myfile . ' ' . Text::_('WIZARD_MANUAL'), $this->getJname());
	            return false;
            } else {
                //parse the file line by line to get only the config variables
	            foreach ($lines as $line) {
		            if (strpos($line, 'define') !== false) {
			            if (strpos($line, 'define') == 0){
				            eval($line);
			            }
		            }
	            }


                //save the parameters into array

                $params['database_host'] = G_DBHOST;
                $params['database_name'] = G_DBNAME;
                $params['database_user'] = G_DBUSER;
                $params['database_password'] = G_DBPASSWD;
                $params['database_type'] = G_DBTYPE;
                $params['source_path'] = $softwarePath;
                $params['md5_key'] = G_MD5KEY;
                $params['uploadpath'] = G_UPLOADPATH;            }
        }
        return $params;
    }

    /**
     * Get a list of users
     *
     * @param int $limitstart
     * @param int $limit
     *
     * @return array
     */
    function getUserList($limitstart = 0, $limit = 0)
    {
	    try {
		    //getting the connection to the db
		    $db = Factory::getDatabase($this->getJname());

		    $query = $db->getQuery(true)
			    ->select('login AS username, email')
		        ->from('#__users');

		    $db->setQuery($query, $limitstart, $limit);
		    //getting the results
		    $userlist = $db->loadObjectList();
	    } catch (Exception $e) {
		    Framework::raise(LogLevel::ERROR, $e, $this->getJname());
		    $userlist = array();
	    }
        return $userlist;
    }

    /**
     * @return int
     */
    function getUserCount()
    {
	    try {
		    //getting the connection to the db
		    $db = Factory::getDatabase($this->getJname());

		    // eFront does not have a single user id field in its userdatabase.
		    // jFusion needs one, so add it here. This routine runs once
		    // when configuring the eFront plugin
		    // Also we need an indication that the module initialisation needs to be performed for this user
		    // because we cannot run this from outside eFront (unless we load the whole framework on top of Joomla)
		    $tableFields = $db->getTableColumns('users', false);
		    if ($tableFields) {
			    if (!array_key_exists('id', $tableFields)) {
				    $query = 'ALTER TABLE users ADD id int(11) NOT null AUTO_INCREMENT FIRST, ADD UNIQUE (id)';
				    $db->setQuery($query);
				    $db->execute();
			    }
			    if (!array_key_exists('need_mod_init', $tableFields)) {
				    $query = 'ALTER TABLE users ADD need_mod_init int(11) NOT null DEFAULT 0';
				    $db->setQuery($query);
				    $db->execute();
			    }
		    }

		    $query = $db->getQuery(true)
			    ->select('count(*)')
			    ->from('#__users');

		    $db->setQuery($query);
		    //getting the results
		    return $db->loadResult();
	    } catch (Exception $e) {
		    Framework::raise(LogLevel::ERROR, $e, $this->getJname());
		    return 0;
	    }
    }

    /**
     * @return array
     */
    function getUsergroupList()
    {
         return $this->helper->getUsergroupList();
    }


    /**
     * @return bool
     */
    function allowRegistration()
    {
	    try {
		    $db = Factory::getDatabase($this->getJname());

		    $query = $db->getQuery(true)
			    ->select('value')
			    ->from('#__configuration')
		        ->where('name = ' . $db->quote('signup'));

		    $db->setQuery($query);
		    $signup = $db->loadResult();
		    if ($signup == 0) {
			    $result = false;
		    } else {
			    $result = true;
		    }
	    } catch (Exception $e) {
		    Framework::raise(LogLevel::ERROR, $e, $this->getJname());
		    $result = false;
	    }
	    return $result;
    }

    /**
     * @return bool
     */
    function allowEmptyCookiePath()
    {
        return true;
    }

    /**
     * @return bool
     */
    function allowEmptyCookieDomain()
    {
        return true;
    }

    function debugConfigExtra()
    {
        // see if we have an api user in Magento
        $db = Factory::getDataBase($this->getJname());
        // check if we have valid parameters  for apiuser and api key
        $apiuser = $this->params->get('apiuser');
        $apikey = $this->params->get('apikey');
        if (!$apiuser || !$apikey) {
                Framework::raise(LogLevel::WARNING, Text::_('EFRONT_NO_API_DATA'), $this->getJname());
        } else {
            //check if the apiuser and apikey are valid
	        $query = $db->getQuery(true)
		        ->select('password')
		        ->from('#__users')
		        ->where('login = ' . $db->quote($apiuser));

            $db->setQuery($query);
            $api_key = $db->loadResult();
            $md5_key = $this->params->get('md5_key');
            $params_hash = md5($apikey . $md5_key);
            if ($params_hash != $api_key) {
                Framework::raise(LogLevel::WARNING, Text::_('EFRONT_WRONG_APIUSER_APIKEY_COMBINATION'), $this->getJname());
            }
        }
        // we need to have the curl library installed
        if (!extension_loaded('curl')) {
            Framework::raise(LogLevel::WARNING, Text::_('CURL_NOTINSTALLED'), $this->getJname());
        }
    }

    /**
     * @return string UNKNOWN or JNO or JYES or ??
     */
    function requireFileAccess()
	{
		return 'JNO';
	}

    /**
     * do plugin support multi usergroups
     *
     * @return bool
     */
    function isMultiGroup()
    {
        return false;
    }
}