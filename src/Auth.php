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

use JFusion\User\Userinfo;

/**
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class Auth extends \JFusion\Plugin\Auth
{
    /**
     * @param Userinfo $userinfo
     *
     * @return string
     */
    function generateEncryptedPassword(Userinfo $userinfo) {
        $md5_key = $this->params->get('md5_key');
        return md5($userinfo->password_clear . $md5_key);
    }
}