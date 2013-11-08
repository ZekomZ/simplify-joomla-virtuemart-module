<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.installer.installer');

class plgVmpaymentSimplifyCommerceInstallerScript {

    function postflight($type, $parent) {              
        $db = JFactory::getDbo();
         
        $extension_id = null;

        if (version_compare (JVERSION, '1.6.0', 'ge')) {

        	$db = JFactory::getDBO();
        	$query =
        	"SELECT " . $db->nameQuote('extension_id') .
        	" FROM " . $db->nameQuote('#__extensions') .
        	" WHERE " . $db->nameQuote('element') ." = " . $db->quote('simplifycommerce').";";
        	$db->setQuery($query);
        	$result = $db->loadResult();
        	$extension_id=$result;

        	if(!empty($result)) {
        		$db = JFactory::getDBO();

        		$query = $db->getQuery(true);
        		$fields = array($db->nameQuote('enabled') . '=1');
        		$conditions = array($db->nameQuote('extension_id') . '=' . $result);

        		$query->update($db->nameQuote('#__extensions'))->set($fields)->where($conditions);
        		$db->setQuery($query);
        		$result = $db->query();
        	}
    	}
    }

    function install($parent) {
         //   Nothing to see here....carry on
    }

    function uninstall($parent) {
       //   Nothing to see here....carry on
    }

    function update($parent) {
       //   Nothing to see here....carry on
    }

    function preflight($type, $parent) {
       //   Nothing to see here....carry on
    }
}
