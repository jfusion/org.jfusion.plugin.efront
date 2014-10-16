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
use JFusion\Plugin\Plugin;

use Joomla\Language\Text;

use Psr\Log\LogLevel;

use \Exception;
use SimpleXMLElement;
use \stdClass;

/**
 * JFusion Hooks for dokuwiki
 *
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class Helper extends Plugin
{
    /**
     * @param $dir
     */
    function delete_directory($dir) {
        $handle = opendir($dir);
	    ob_start();
        if ($handle) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if(is_dir($dir . $file)) {
                        if(!rmdir($dir . $file)){ // Empty directory? Remove it
                            $this->delete_directory($dir . $file . '/'); // Not empty? Delete the files inside it
                        }
                    } else {
                        unlink($dir . $file);
                    }
                }
            }
            closedir($handle);
            rmdir($dir);
        }
	    ob_end_clean();
    }

    /**
     * @param $user_type
     * @param $user_types_ID
     *
     * @return int
     */
    function groupNameToID($user_type, $user_types_ID) {
        $groupid = 0;
        if ($user_types_ID == 0) {
            switch ($user_type) {
                case 'professor':
	                $groupid = 1;
                    break;
                case 'administrator':
	                $groupid = 2;
                    break;
           }    
        } else {
	        $groupid = $user_types_ID+2;
        }
        return $groupid;
    }

    /**
     * @param $groupid
     *
     * @return bool|string
     */
    function groupIdToName ($groupid) {
        switch ($groupid){
           case 0: return 'student';
           case 1: return 'professor';
           case 2: return 'administrator';
        }

	    try {
		    // correct id
		    $groupid = $groupid - 2;
		    $db = Factory::getDatabase($this->getJname());
		    if (!empty($db)) {
			    $query = $db->getQuery(true)
				    ->select('name, basic_user_type')
				    ->from('#__user_types')
				    ->where('id = ' . $db->quote($groupid));

			    $db->setQuery($query);
			    $user_type = (array)$db->loadObject();
			    return $user_type['name'] . ' (' . $user_type['basic_user_type'] . ')';
		    }
	    } catch (Exception $e) {
			Framework::raise(LogLevel::ERROR, $e, $this->getJname());
	    }
        return false;
    }

    /**
     * @return array
     */
    function getUsergroupList() {
        // efront has three build in user_types: student, professor and administrator
        // you can add additional usertypes from these,
        // but every additional usertype forks from the above basic types
        // in order to map this to the linear usergroup list of jFusion and to allow extended
        // group synchronisation the list will be build as follows (ID= internal jFusion usergroup id)
        // Id   type
        //  0   student (basic new account)
        //  1   professor
        //  2   administrator
        //  3   first record (1) user_types table
        //  4   next record in yser_types table
        //  etc
        // as there is no protection for duplicate usertype names we will display
        // the basic usertype between brackets, eg  record 3 is displayed as testtype (student)
        // if it has a basic type : student.
        // there is no check in the admin software of efront on duplicate usertypes (same name/basic usertype)
        // this won't harm jFusion but can confuse admin when selection usergroups to sync.
        // should make note so duplicate groups in efront is not a jFusion bug.

        $user_types = array();
	    $group = new stdClass;
	    $group->id = '0';
	    $group->name = 'student';
	    $user_types[] = $group;

	    $group = new stdClass;
	    $group->id = '1';
	    $group->name = 'professor';
	    $user_types[] = $group;

	    $group = new stdClass;
	    $group->id = '2';
	    $group->name = 'administrator';
	    $user_types[] = $group;

		try {
	        //get the connection to the db
	        $db = Factory::getDatabase($this->getJname());

			$query = $db->getQuery(true)
				->select('id, name, basic_user_type')
				->from('#__user_types');

	        $db->setQuery($query);
	        //getting the results
	        $additional_types = $db->loadObjectList();
	        // construct the array
	        foreach ($additional_types as $usertype){
		        $group = new stdClass;
		        $group->id = $usertype->id+2;
		        $group->name = $usertype->name . ' (' . $usertype->basic_user_type . ')';
		        $user_types[] = $group;
	        }
	    } catch (Exception $e) {
			Framework::raise(LogLevel::ERROR, $e, $this->getJname());
		}
        return $user_types;
    }

	/**
	 * connects to api, using username and password
	 * returns token, or empty string when not successful
	 *
	 * @param array $curl_options
	 *
	 * @throws \RuntimeException
	 * @return SimpleXMLElement|null
	 */
    function sendToApi($curl_options) {
	    $result = null;
        $source_url = Factory::getParams($this->getJname())->get('source_url');
        // prevent user error by not supplying trailing backslash.
        if (!(substr($source_url, -1) == '/')) {
            $source_url = $source_url . '/';
        }    
        //prevent user error by preventing a heading forward slash
        ltrim($source_url);
        $apipath = $source_url . 'api.php?action=';
        $post_url = $apipath . $curl_options['action'] . $curl_options['parms'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_REFERER, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!empty($curl_options['httpauth'])) {
            curl_setopt($ch, CURLOPT_USERPWD, "{$curl_options['httpauth_username']}:{$curl_options['httpauth_password']}");

            switch ($curl_options['httpauth']) {
            case "basic":
                $curl_options['httpauth'] = CURLAUTH_BASIC;
                break;
            case "gssnegotiate":
                $curl_options['httpauth'] = CURLAUTH_GSSNEGOTIATE;
                break;
            case "digest":
                $curl_options['httpauth'] = CURLAUTH_DIGEST;
                break;
            case "ntlm":
                $curl_options['httpauth'] = CURLAUTH_NTLM;
                break;
            case "anysafe":
                $curl_options['httpauth'] = CURLAUTH_ANYSAFE;
                break;
            case "any":
            default:
                $curl_options['httpauth'] = CURLAUTH_ANY;
            }

            curl_setopt($ch, CURLOPT_HTTPAUTH, $curl_options['httpauth']);
        }
        $remotedata = curl_exec($ch);
        if (curl_error($ch)) {
	        throw new \RuntimeException(Text::_('EFRONT_API_POST') . ' ' . Text::_('CURL_ERROR_MSG') . ': ' . curl_error($ch));
        } else {
	        $result = simplexml_load_string($remotedata);
        }
        curl_close($ch);
        return $result;
    }
}