<?php
$magento_folder = str_replace('\js\pdp\instagram','',dirname(__FILE__));
$magento_folder = str_replace('/js/pdp/instagram','',$magento_folder);
require_once $magento_folder . '/app/Mage.php';
Mage::app();
require_once 'lib/instagram.php';

$client_id = Mage::getStoreConfig('pdp/customer_action/instagram_api');
$client_secret = Mage::getStoreConfig('pdp/customer_action/instagram_key');
echo $redirect_uri = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS).'pdp/instagram/redirect.php';

$uploader = new InstagramUploader($client_id, $client_secret, $redirect_uri, $_GET['code']);
$uploader->init();

?>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript" src="pdc_ins_results.js"></script>
