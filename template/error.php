<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$error_message = $params['message'];
$payment_error = $params['payment_error'];
?>
<? if ($payment_error) :?>
<h3><?= Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_ERROR_PAYMENT') ?></h3>
<? else :?>
<p><?= Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_ERROR_INVOICE') ?><b><?= $error_message ?></b></p>
<? endif ?>