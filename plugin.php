<?php




use \Omeka_Record;
if (!defined('REST_PLUGIN_DIR')) {
    define('REST_PLUGIN_DIR', dirname(__FILE__));
}
require_once(REST_PLUGIN_DIR.DIRECTORY_SEPARATOR.'libTimeline.php');

/**
 * This class ... 
 */
class Rest extends Omeka_Plugin_Abstract {

/**
 * The hooks that you declare you are 
 * using in the $_hooks array 
 * must have a corresponding public 
 * method of the form hook{Hookname} as above. 
 */
    protected $_hooks = array(
//        'install', 
        'initialize', 
//        'public_theme_header', 
//        'public_theme_body', 
//        'uninstall', 
//        'define_acl'
    );

    /**
     *
     * @var string[] the aset of filters we are implementing
     */
    //protected $_filters = array('admin_navigation_main');


    public function hookInitialize() {
        

    }

    /**
     * @param type $request 
     */
    public function hookPublicThemeHeader($request) {
        
    }

    /**
     * Insert something into the theme body
     * @param type $request 
     */
    public function hookPublicThemeBody($request) {

    }

    /**
     * Do things when the user clicks install, 
     * like build DB tables, etc
     * @throws Exception
     */
    function hookInstall() {
        
    }
    
    
    /**
     * when the user deletes us from Omeka, cleanup after ourselves by DROPPING or db tables, etc
     */
    function hookUninstall() {
        
    }
    
    
    /**
     * 
     * 
     * 
     */
    function filterAdminNavigationMain() {
        
    }

    
    /**
     * 
     * This hook runs when Omeka's ACL is instantiated. 
     * It allows plugin writers to manipulate the ACL 
     * that controls user access in Omeka.
     * In general, plugins use this hook to restrict 
     * and/or allow access for specific user roles 
     * to the pages that it creates. 
     * @param Zend_Acl $acl The ACL object (a subclass of Zend_Acl)
     */
    function hookDefineAcl($acl) {
        
    }
    
    
}

$rest = new Rest();

