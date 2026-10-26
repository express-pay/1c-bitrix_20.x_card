<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$payment_error = $params['payment_error'];
?>
<? if ($payment_error) :?>
<h3><?= Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_ERROR_PAYMENT') ?></h3>
<? else :?>
<p><?= Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_ERROR_INVOICE') ?><b><?= htmlspecialcharsbx($params['message'] ?? Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_ERROR_GENERAL')) ?></b></p>
<? endif ?>