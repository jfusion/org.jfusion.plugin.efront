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

/**
 * @category   Plugins
 * @package    JFusion\Plugins
 * @subpackage efront
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class Front extends \JFusion\Plugin\Front
{
    /**
     * @return string
     */
    function getRegistrationURL()
    {
        return 'index.php?ctg=signup';
    }

    /**
     * @return string
     */
    function getLostPasswordURL()
    {
        return 'index.php?ctg=reset_pwd';
    }

    /**
     * @return string
     */
    function getLostUsernameURL()
    {
        return 'index.php?ctg=reset_pwd';
    }
}