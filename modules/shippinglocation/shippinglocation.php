<?php

/*

* 2007-2013 PrestaShop

*

* NOTICE OF LICENSE

*

* This source file is subject to the Academic Free License (AFL 3.0)

* that is bundled with this package in the file LICENSE.txt.

* It is also available through the world-wide-web at this URL:

* http://opensource.org/licenses/afl-3.0.php

* If you did not receive a copy of the license and are unable to

* obtain it through the world-wide-web, please send an email

* to license@prestashop.com so we can send you a copy immediately.

*

* DISCLAIMER

*

* Do not edit or add to this file if you wish to upgrade PrestaShop to newer

* versions in the future. If you wish to customize PrestaShop for your

* needs please refer to http://www.prestashop.com for more information.

*

*  @author PrestaShop SA <contact@prestashop.com>

*  @copyright  2007-2013 PrestaShop SA

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)

*  International Registered Trademark & Property of PrestaShop SA

*/



if (!defined('_PS_VERSION_'))

	exit;



class ShippingLocation extends Module

{

	



	public function __construct()

	{

		$this->name = 'shippinglocation';

		$this->tab = 'advertising_marketing';

		$this->version = '1.0';

		$this->author = 'Nvision Softech';

		$this->need_instance = 0;



		$this->displayName = $this->l('Shipping Location');

		$this->description = $this->l('Shipping Module Description');

		parent::__construct();

	

	}



	

	

	public function install()

	{

		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_stores` (

            `id` int(11) NOT NULL AUTO_INCREMENT,

            `customer_id` int(11) NOT NULL,

            `store_id` int(11) NOT NULL,

             PRIMARY KEY (`id`)

          ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';

$query = "ALTER TABLE  `"._DB_PREFIX_."order_carrier` 
ADD  `store_id` INT NOT NULL AFTER  `date_add`";
   
		if(!Db::getInstance()->Execute($query))
  		return false;

	  if(!Db::getInstance()->Execute($sql))
	
	   return false;


		return (parent::install()&& $this->registerHook('BeforeCarrier') && $this->registerHook('OrderConfirmation') && $this->registerHook('displayOrderDetail'));

	}

	

	public function uninstall()

	{

		$query = "ALTER TABLE `"._DB_PREFIX_."order_carrier`  DROP `store_id`";
   
		if(!Db::getInstance()->Execute($query))
  		return false;

		return (parent::uninstall());

	}


	
	public function renderStoreWorkingHours($store)
	{
		global $smarty;
		
		$days[1] = 'Monday';
		$days[2] = 'Tuesday';
		$days[3] = 'Wednesday';
		$days[4] = 'Thursday';
		$days[5] = 'Friday';
		$days[6] = 'Saturday';
		$days[7] = 'Sunday';
		
		$days_datas = array();
		$hours = array_filter(unserialize($store['hours']));
		if (!empty($hours))
		{
			for ($i = 1; $i < 8; $i++)
			{
				if (isset($hours[(int)($i) - 1]))
				{
					$hours_datas = array();
					$hours_datas['hours'] = $hours[(int)($i) - 1];
					$hours_datas['day'] = $days[$i];
					$days_datas[] = $hours_datas;
				}
			}
			$smarty->assign('days_datas', $days_datas);
			$smarty->assign('id_country', $store['id_country']);
			return $this->context->smarty->fetch(_PS_THEME_DIR_.'store_infos.tpl');
		}
		return false;
	}

	

	

	public function hookBeforeCarrier($params)

	{

	$sql = 'SELECT * FROM '._DB_PREFIX_.'store';

	$results = Db::getInstance()->ExecuteS($sql);

$user_id=$this->context->customer->id;
$pincode = '';
	if($user_id != ''){
 $sql = 'SELECT * FROM '._DB_PREFIX_.'address where id_customer='.$user_id;

	$adress = Db::getInstance()->getRow($sql);

    $pincode=$adress['postcode'];
	}
		
$distanceUnit = Configuration::get('PS_DISTANCE_UNIT');
		if (!in_array($distanceUnit, array('km', 'mi')))
			$distanceUnit = 'mi';
			
		
$distanceUnit = 'mi';
if (Configuration::get('PS_STORES_SIMPLIFIED'))
			$this->assignStoresSimplified();
		else
			$this->assignStores();
	

$customer_id=$this->context->customer->id;
 $default_store_id = '';
	 $store_name = '';
	 $store_data = array();
if($customer_id != ''){
 $sql = 'SELECT * FROM '._DB_PREFIX_.'customer_stores where customer_id='.$customer_id;

	$store_id = Db::getInstance()->getRow($sql);
	
    $default_store_id=$store_id['store_id'];
	}
	
	if($default_store_id != ''){
	
 $sql = 'SELECT * FROM '._DB_PREFIX_.'store where id_store='.$default_store_id;



	$store_data = Db::getInstance()->getRow($sql);
	
	
$store_name = $store_data['name'];
			
	}
		
			
			
				$this->smarty->assign(array(

		'data' => $results,
		'default_store_id' =>$default_store_id,
		
         'defaultLat' => (float)Configuration::get('PS_STORES_CENTER_LAT'),
		 'defaultLong' => (float)Configuration::get('PS_STORES_CENTER_LONG'),
		'pincode'=>$pincode,
           'distance_unit' => $distanceUnit,
			'simplifiedStoresDiplay' => false,
			'stores' => $this->getStores(),
			'store_data'=>$store_data,
			'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
			'defaultLat' => (float)Configuration::get('PS_STORES_CENTER_LAT'),
			'defaultLong' => (float)Configuration::get('PS_STORES_CENTER_LONG'),
			'searchUrl' => $this->context->link->getPageLink('stores'),
			'logo_store' => Configuration::get('PS_STORES_ICON')
			
		
		

	   ));

		return $this->display(__FILE__, 'shippinglocation.tpl');

	}
	
	public function getStores()
	{
		$distanceUnit = Configuration::get('PS_DISTANCE_UNIT');
		if (!in_array($distanceUnit, array('km', 'mi')))
			$distanceUnit = 'km';

		if (Tools::getValue('all') == 1)
		{
			$stores = Db::getInstance()->executeS('
			SELECT s.*, cl.name country, st.iso_code state
			FROM '._DB_PREFIX_.'store s
			'.Shop::addSqlAssociation('store', 's').'
			LEFT JOIN '._DB_PREFIX_.'country_lang cl ON (cl.id_country = s.id_country)
			LEFT JOIN '._DB_PREFIX_.'state st ON (st.id_state = s.id_state)
			WHERE s.active = 1 AND cl.id_lang = '.(int)$this->context->language->id);
		}
		else
		{
			$distance = (int)(Tools::getValue('radius', 100));
			$multiplicator = ($distanceUnit == 'km' ? 6371 : 3959);

			$stores = Db::getInstance()->executeS('
			SELECT s.*, cl.name country, st.iso_code state,
			('.(int)($multiplicator).'
				* acos(
					cos(radians('.(float)(Tools::getValue('latitude')).'))
					* cos(radians(latitude))
					* cos(radians(longitude) - radians('.(float)(Tools::getValue('longitude')).'))
					+ sin(radians('.(float)(Tools::getValue('latitude')).'))
					* sin(radians(latitude))
				)
			) distance,
			cl.id_country id_country
			FROM '._DB_PREFIX_.'store s
			'.Shop::addSqlAssociation('store', 's').'
			LEFT JOIN '._DB_PREFIX_.'country_lang cl ON (cl.id_country = s.id_country)
			LEFT JOIN '._DB_PREFIX_.'state st ON (st.id_state = s.id_state)
			WHERE s.active = 1 AND cl.id_lang = '.(int)$this->context->language->id.'
			HAVING distance < '.(int)($distance).'
			ORDER BY distance ASC
			LIMIT 0,20');
		}

		return $stores;
	}


protected function assignStoresSimplified()
	{
		$stores = Db::getInstance()->executeS('
		SELECT s.*, cl.name country, st.iso_code state
		FROM '._DB_PREFIX_.'store s
		'.Shop::addSqlAssociation('store', 's').'
		LEFT JOIN '._DB_PREFIX_.'country_lang cl ON (cl.id_country = s.id_country)
		LEFT JOIN '._DB_PREFIX_.'state st ON (st.id_state = s.id_state)
		WHERE s.active = 1 AND cl.id_lang = '.(int)$this->context->language->id);

		foreach ($stores as &$store)
		{
			$store['has_picture'] = file_exists(_PS_STORE_IMG_DIR_.(int)($store['id_store']).'.jpg');
			if ($working_hours = $this->renderStoreWorkingHours($store))
				$store['working_hours'] = $working_hours;
		}

		$this->context->smarty->assign(array(
			'simplifiedStoresDiplay' => true,
			'stores' => $stores
		));
	}
	
	protected function assignStores()
	{
		$this->context->controller->addCSS($this->_path.'css/stores.css', 'all');

		

		if (!Configuration::get('PS_STORES_SIMPLIFIED'))

			

			$this->context->controller->addJS($this->_path.'js/stores.js');

        $default_country = new Country((int)Configuration::get('PS_COUNTRY_DEFAULT'));

		$this->context->controller->addJS('http://maps.google.com/maps/api/js?sensor=true&amp;region='.substr($default_country->iso_code, 0, 2));

		$this->context->smarty->assign('hasStoreIcon', file_exists(_PS_IMG_DIR_.Configuration::get('PS_STORES_ICON')));

		$distanceUnit = Configuration::get('PS_DISTANCE_UNIT');
		if (!in_array($distanceUnit, array('km', 'mi')))
			$distanceUnit = 'km';

		$this->context->smarty->assign(array(
			'distance_unit' => $distanceUnit,
			'simplifiedStoresDiplay' => false,
			'stores' => $this->getStores()
		));
	}
	function hookOrderConfirmation($params)    {     
	
	$customer_id=$this->context->customer->id;
	
	$order_id = Tools::getValue('id_order');

  $sql = 'SELECT * FROM '._DB_PREFIX_.'customer_stores where customer_id='.$customer_id;

   $results = Db::getInstance()->getRow($sql);
	
	
	
	$store_id=$results['store_id'];
	
$carrier_id = 	$params['objOrder']->id_carrier;

	 DB::getInstance()->Execute('UPDATE  `'._DB_PREFIX_.'order_carrier` set `store_id`='.$store_id.' where id_order ='.$order_id);
	 
	
		
		
		
	}
	
	function hookDisplayOrderDetail($params){
		
	
		


$order_id = Tools::getValue('id_order');

$customer_id=$this->context->customer->id;


// $customer_id=$order_id->id_customer;
 $sql = 'SELECT * FROM '._DB_PREFIX_.'order_carrier where id_order='.$order_id;
   $store_id = Db::getInstance()->getRow($sql);
   


	
	 $default_store_id = '';
	 $store_name = '';
	 
	 
	 $default_store_id=$store_id['store_id'];
	
	if($default_store_id != ''){	
 $sql = 'SELECT * FROM '._DB_PREFIX_.'store where id_store='.$default_store_id;

	$store_data = Db::getInstance()->getRow($sql);

	
	
	
	
$store_name = $store_data['name'];
$this->context->smarty->assign(array(
'store_name1'=>$store_name,
'address_store'=>$store_data['address1'],
));
	}
		
		//return 'hello ----- ';
		return $this->display(__FILE__, 'orderDetail.tpl');
		
		}
	
	

}