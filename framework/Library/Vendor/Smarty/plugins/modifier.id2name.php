<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty escape modifier plugin
 *
 * Type:     modifier<br>
 * Name:     escape<br>
 * Purpose:  escape string for output
 *
 * @link http://www.smarty.net/manual/en/language.modifier.count.characters.php count_characters (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param string  $string        input string
 * @param string  $esc_type      escape type
 * @param string  $char_set      character set, used for htmlspecialchars() or htmlentities()
 * @param boolean $double_encode encode already encoded entitites again, used for htmlspecialchars() or htmlentities()
 * @return string escaped input string
 */
function smarty_modifier_id2name($string, $idKey='benefit')
{
    switch ($idKey) {
        case 'benefit':
        	$mod = D('Benefit');
        	$row = $mod->getById($string);
        	
        	return isset($row['name'])?$row['name']:'N/A';

        case 'letter':
        	$mod = D('MessageObject');
        	$name = $mod->toDesc($string);
			
        	return $name;

        case 'benefitCategory':
        	$mod = D('BenefitCategory');
        	$name = $mod->toDesc($string);
			
        	return $name;

        case 'adminUser':
	        $mod = D('AdminUser');
        	if ($string < 0){//虚拟用户
        		$name =  $mod->getByVirId($string);
        		return $name;
        	}else{
	        	$row = $mod->getById($string);
	        	
	        	return isset($row['name'])?$row['name']:'N/A';
        	}
        default:
            return $string;
    }
}

?>