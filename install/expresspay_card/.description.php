<?php

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\PaySystem;

Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$protocol = $request->isHttps() ? 'https://' : 'http://';

// Доверенный хост только из настроек; никакого fallback на заголовок Host
$currentHost = trim((string)\Bitrix\Main\Config\Option::get('main', 'server_name'));
if (empty($currentHost)) {
	$currentHost = '';
}

$notificationWarning = '';
$signatureWarning = '';
if (!empty($_REQUEST['ID']) && Loader::includeModule('sale')) {
	$psId = (int)$_REQUEST['ID'];
	$dbRes = \CSalePaySystemAction::GetList([], ['PAY_SYSTEM_ID' => $psId]);
	$ps = $dbRes->Fetch();
	if (!empty($ps)) {
		$params = [];
		if (!empty($ps['PARAMS'])) {
			$params = is_array($ps['PARAMS']) ? $ps['PARAMS'] : unserialize($ps['PARAMS'], ['allowed_classes' => false]);
		}
		$savedUrl = $params['CARD_NOTIFICATION_URL']['VALUE'] ?? '';
		if (!empty($savedUrl)) {
			$parsedUrl = parse_url($savedUrl);
			$savedHost = $parsedUrl['host'] ?? '';
			if (!empty($savedHost) && strtolower($savedHost) !== strtolower($currentHost)) {
				$notificationWarning = '<div class="adm-info-message-wrap adm-info-message-red"><div class="adm-info-message"><div class="adm-info-message-title">' . Loc::getMessage('CARD_NOTIFICATION_URL_WARNING') . '</div><div>' . Loc::getMessage('CARD_NOTIFICATION_URL_WARNING_DETAIL', ['#URL#' => htmlspecialcharsbx($savedUrl), '#HOST#' => htmlspecialcharsbx($currentHost)]) . '</div></div></div>';
			}
		}
		$isUseSignatureValue = $params['CARD_IS_USE_SIGNATURE_FROM_NOTIFICATION']['VALUE'] ?? null;
		$isUseSignature = ($isUseSignatureValue === 'Y' || $isUseSignatureValue === 1 || $isUseSignatureValue === '1') ? 'Y' : 'N';
		$secretWord = $params['CARD_SECRET_WORD_FROM_NOTIFICATION']['VALUE'] ?? '';
		if ($isUseSignature === 'Y' && empty($secretWord)) {
			$signatureWarning = '<div class="adm-info-message-wrap adm-info-message-red"><div class="adm-info-message"><div class="adm-info-message-title">' . Loc::getMessage('CARD_SIGNATURE_CONFIG_WARNING') . '</div><div>' . Loc::getMessage('CARD_SIGNATURE_CONFIG_WARNING_DETAIL') . '</div></div></div>';
		}
		if ($isUseSignatureValue !== null && $isUseSignature !== 'Y') {
			$signatureWarning .= '<div class="adm-info-message-wrap adm-info-message-red"><div class="adm-info-message"><div class="adm-info-message-title">' . Loc::getMessage('CARD_SIGNATURE_DISABLED_WARNING') . '</div></div></div>';
		}
	}
}

if (!empty($notificationWarning) || !empty($signatureWarning)) {
	echo '<div style="margin-bottom:15px;">';
	if (!empty($notificationWarning)) {
		echo $notificationWarning;
	}
	if (!empty($signatureWarning)) {
		echo $signatureWarning;
	}
	echo '</div>';
}

$isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_TRUE;

$portalZone = Loader::includeModule('intranet') ? CIntranetUtils::getPortalZone() : '';
$licensePrefix = Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : '';

if (Loader::includeModule("bitrix24")) {
	if ($licensePrefix !== 'by') {
		$isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
	}
} elseif (Loader::includeModule('intranet') && $portalZone !== 'ru') {
	$isAvailable = PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
}

$description = Loc::getMessage('SALE_EXPRESSPAY_CARD_DESCRIPTION');

$data = [
	'NAME' => Loc::getMessage('SALE_EXPRESSPAY_CARD_TITLE'),
	'SORT' => 100,
	'IS_AVAILABLE' => $isAvailable,
	'CODES' => [
		"CARD_IS_TEST_API" => [
			"SORT" => 10,
			"NAME" => Loc::getMessage("CARD_IS_TEST_API_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_IS_TEST_API_DESCR"),
			"INPUT" => [
				'TYPE' => 'Y/N'
			],
			'DEFAULT' => [
				"PROVIDER_VALUE" => "N",
				"PROVIDER_KEY" => "INPUT"
			],
		],
		"CARD_TOKEN"	=> [
			"SORT" => 20,
			"NAME"	=> Loc::getMessage("CARD_TOKEN_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_TOKEN_DESCR"),
			"VALUE"	=> "",
			"TYPE"	=> ""
		],
		"CARD_SERVICE_ID"	=> [
			"SORT" => 30,
			"NAME"	=> Loc::getMessage("CARD_SERVICE_ID_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_SERVICE_ID_DESCR"),
			"VALUE"	=> "",
			"TYPE"	=> ""
		],
		"CARD_SECRET_WORD"	=> [
			"SORT" => 50,
			"NAME"	=> Loc::getMessage("CARD_SECRET_WORD_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_SECRET_WORD_DESCR"),
			"DEFAULT" => [
				"PROVIDER_VALUE" => "",
				"PROVIDER_KEY" => "VALUE"
			],
		],
		"CARD_NOTIFICATION_URL"	=> [
			"SORT" => 55,
			"NAME"	=> Loc::getMessage("CARD_NOTIFICATION_URL_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_NOTIFICATION_URL_DESCR"),
			"DEFAULT" => [
				"PROVIDER_VALUE" => $protocol.$currentHost.'/bitrix/tools/sale_ps_result.php',
				"PROVIDER_KEY" => "VALUE"
			],
		],
		"CARD_IS_USE_SIGNATURE_FROM_NOTIFICATION" => [
			"SORT" => 60,
			"NAME" => Loc::getMessage("CARD_IS_USE_SIGNATURE_FROM_NOTIFICATION_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_IS_USE_SIGNATURE_FROM_NOTIFICATION_DESCR"),
			"INPUT" => [
				'TYPE' => 'Y/N'
			],
			'DEFAULT' => [
				"PROVIDER_VALUE" => "Y",
				"PROVIDER_KEY" => "INPUT"
			],
		],
		"CARD_SECRET_WORD_FROM_NOTIFICATION"	=> [
			"SORT" => 65,
			"NAME"	=> Loc::getMessage("CARD_SECRET_WORD_FROM_NOTIFICATION_NAME"),
			"DESCRIPTION"	=> Loc::getMessage("CARD_SECRET_WORD_FROM_NOTIFICATION_DESCR"),
			"DEFAULT" => [
				"PROVIDER_VALUE" => "",
				"PROVIDER_KEY" => "VALUE"
			],
		],
		"CARD_INFO_TEMPLATE"	=> array(
			"SORT" => 60,
			"NAME"	=> Loc::getMessage("CARD_INFO_TEMPLATE_NAME"),
			"DESCR"	=> Loc::getMessage("CARD_INFO_TEMPLATE_DESCR"),
			"DEFAULT" => array(
				"PROVIDER_VALUE" => Loc::getMessage("CARD_INFO_TEMPLATE_PROVIDER_VALUE"),
				"PROVIDER_KEY" => "VALUE"
			)
		),
	]
];
