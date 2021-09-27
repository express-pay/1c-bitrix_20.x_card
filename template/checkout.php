<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$sum = round($params['sum'], 2);
$currency = $params['currency'];
?>
<?= Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_DESCRIPTION', ['##ORDER_ID##' => $sum, '##CURRENCY##' => $currency]) ?>