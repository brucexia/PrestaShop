<?php

require_once(dirname(__FILE__).'../../../config/config.inc.php');



require_once(dirname(__FILE__).'../../../init.php');

$store_id = $_REQUEST['store_id'];
 


  $context = Context::getContext();

 $id_customer = $context->customer->id;

 $sql = 'SELECT * FROM '._DB_PREFIX_.'customer_stores where customer_id='.$id_customer;

$results = Db::getInstance()->getRow($sql);


if(!empty($results)){
	
	$id = $results['id'];
	 DB::getInstance()->Execute('UPDATE  `'._DB_PREFIX_.'customer_stores` set `customer_id`='.$id_customer.',`store_id`='.$store_id.' where id='.$id.'');
	  
	
	
	}else{
		
	
		
		 DB::getInstance()->Execute('

    INSERT INTO `'._DB_PREFIX_.'customer_stores` (`customer_id`,`store_id`) VALUES ('.$id_customer.','.$store_id.')');

		
		
		}
 
?>







 