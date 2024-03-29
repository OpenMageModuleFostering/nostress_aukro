<?php 
/** 
* Magento Module developed by NoStress Commerce 
* 
* NOTICE OF LICENSE 
* 
* This source file is subject to the Open Software License (OSL 3.0) 
* that is bundled with this package in the file LICENSE.txt. 
* It is also available through the world-wide-web at this URL: 
* http://opensource.org/licenses/osl-3.0.php 
* If you did of the license and are unable to 
* obtain it through the world-wide-web, please send an email 
* to info@nostresscommerce.cz so we can send you a copy immediately. 
* 
* @copyright Copyright (c) 2012 NoStress Commerce (http://www.nostresscommerce.cz) 
* 
*/

/** 
* Exports model - source for dropdown menu "Product group size"
* 
* @category Nostress 
* @package Nostress_Aukro
*
*/

class Nostress_Aukro_Model_Config_Source_Encoding
{
	const LABEL = 'label';
	const VALUE = 'value';
	const COUNTRY = 'country';
	
	protected $_options;
	
	public function toOptionArray() 
	{		
		$options = array('utf-8' => 'utf-8');
        $options[] = array(
                'label' => 'ISO (Unix/Linux)', 'value' => array(
                    array(self::VALUE => 'iso-8859-1',self::LABEL => 'iso-8859-1'),
                    array(self::VALUE => 'iso-8859-2' ,self::LABEL => 'iso-8859-2'),
                    array(self::VALUE => 'iso-8859-3' ,self::LABEL => 'iso-8859-3'),
                    array(self::VALUE => 'iso-8859-4' ,self::LABEL => 'iso-8859-4'),
                    array(self::VALUE => 'iso-8859-5' ,self::LABEL => 'iso-8859-5'),
                    array(self::VALUE => 'iso-8859-6' ,self::LABEL => 'iso-8859-6'),
                    array(self::VALUE => 'iso-8859-7' ,self::LABEL => 'iso-8859-7'),
                    array(self::VALUE => 'iso-8859-8' ,self::LABEL => 'iso-8859-8'),
                    array(self::VALUE => 'iso-8859-9' ,self::LABEL => 'iso-8859-9'),
                    array(self::VALUE => 'iso-8859-10' ,self::LABEL => 'iso-8859-10'),
                    array(self::VALUE => 'iso-8859-11' ,self::LABEL => 'iso-8859-11'),
                    array(self::VALUE => 'iso-8859-12' ,self::LABEL => 'iso-8859-12'),
                    array(self::VALUE => 'iso-8859-13' ,self::LABEL => 'iso-8859-13'),
                    array(self::VALUE => 'iso-8859-14' ,self::LABEL => 'iso-8859-14'),
                    array(self::VALUE => 'iso-8859-15' ,self::LABEL => 'iso-8859-15'),
                    array(self::VALUE => 'iso-8859-16' ,self::LABEL => 'iso-8859-16'),
                 ));
   		$options[] = array(
                'label' => 'WINDOWS', 'value' => array(
                    array(self::VALUE => 'windows-1250' ,self::LABEL => 'windows-1250 - Central Europe'),
                    array(self::VALUE => 'windows-1251' ,self::LABEL => 'windows-1251 - Cyrillic'),
                    array(self::VALUE => 'windows-1252' ,self::LABEL => 'windows-1252 - Latin I'),
                    array(self::VALUE => 'windows-1253' ,self::LABEL => 'windows-1253 - Greek'),
                    array(self::VALUE => 'windows-1254' ,self::LABEL => 'windows-1254 - Turkish'),
                    array(self::VALUE => 'windows-1255' ,self::LABEL => 'windows-1255 - Hebrew'),
                    array(self::VALUE => 'windows-1256' ,self::LABEL => 'windows-1256 - Arabic'),
                    array(self::VALUE => 'windows-1257' ,self::LABEL => 'windows-1257 - Baltic'),
                    array(self::VALUE => 'windows-1258' ,self::LABEL => 'windows-1258 - Viet Nam'),
                ));
		$options[] = array(
                'label' => 'DOS', 'value' => array(
                    array(self::VALUE => 'cp437' ,self::LABEL => 'cp437 - Latin US'),
                    array(self::VALUE => 'cp737' ,self::LABEL => 'cp737 - Greek'),
                    array(self::VALUE => 'cp775' ,self::LABEL => 'cp775 - BaltRim'),
                    array(self::VALUE => 'cp850' ,self::LABEL => 'cp850 - Latin1'),
                    array(self::VALUE => 'cp852' ,self::LABEL => 'cp852 - Latin2'),
                    array(self::VALUE => 'cp855' ,self::LABEL => 'cp855 - Cyrylic'),
                    array(self::VALUE => 'cp857' ,self::LABEL => 'cp857 - Turkish'),
                    array(self::VALUE => 'cp860' ,self::LABEL => 'cp860 - Portuguese'),
                    array(self::VALUE => 'cp861' ,self::LABEL => 'cp861 - Iceland'),
                    array(self::VALUE => 'cp862' ,self::LABEL => 'cp862 - Hebrew'),
                    array(self::VALUE => 'cp863' ,self::LABEL => 'cp863 - Canada'),
                    array(self::VALUE => 'cp864' ,self::LABEL => 'cp864 - Arabic'),
                    array(self::VALUE => 'cp865' ,self::LABEL => 'cp865 - Nordic'),
                    array(self::VALUE => 'cp866' ,self::LABEL => 'cp866 - Cyrylic Russian (used in IE "Cyrillic (DOS)" )'),
                    array(self::VALUE => 'cp869' ,self::LABEL => 'cp869 - Greek2'),
                ));
		$options[] = array(
                'label' => 'MAC (Apple)', 'value' => array(
                    array(self::VALUE => 'x-mac-cyrillic' ,self::LABEL => 'x-mac-cyrillic'),
                    array(self::VALUE => 'x-mac-greek' ,self::LABEL => 'x-mac-greek'),
                    array(self::VALUE => 'x-mac-icelandic' ,self::LABEL => 'x-mac-icelandic'),
                    array(self::VALUE => 'x-mac-ce' ,self::LABEL => 'x-mac-ce'),
                    array(self::VALUE => 'x-mac-roman' ,self::LABEL => 'x-mac-roman'),
				));
		$options[] = array(
                'label' => 'MISCELLANEOUS', 'value' => array(
                    array(self::VALUE => 'gsm0338' ,self::LABEL => 'gsm0338 (ETSI GSM 03.38)'),
                    array(self::VALUE => 'cp037' ,self::LABEL => 'cp037'),
                    array(self::VALUE => 'cp424' ,self::LABEL => 'cp424'),
                    array(self::VALUE => 'cp500' ,self::LABEL => 'cp500'),
                    array(self::VALUE => 'cp856' ,self::LABEL => 'cp856'),
                    array(self::VALUE => 'cp875' ,self::LABEL => 'cp875'),
                    array(self::VALUE => 'cp1006' ,self::LABEL => 'cp1006'),
                    array(self::VALUE => 'cp1026' ,self::LABEL => 'cp1026'),
                    array(self::VALUE => 'koi8-r' ,self::LABEL => 'koi8-r (Cyrillic)'),
                    array(self::VALUE => 'koi8-u' ,self::LABEL => 'koi8-u (Cyrillic Ukrainian)'),
                    array(self::VALUE =>  'nextstep' ,self::LABEL => 'nextstep'),
                    array(self::VALUE =>  'us-ascii' ,self::LABEL => 'us-ascii'),
                    array(self::VALUE => 'us-ascii-quotes' ,self::LABEL => 'us-ascii-quotes'),
				));
                           
		return $options;				
	}
}
?>