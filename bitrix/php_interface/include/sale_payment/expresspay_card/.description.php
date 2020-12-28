<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$psTitle = GetMessage("SALE_EXPRESSPAY_CARD_TITLE");
$psDescription = GetMessage("SALE_EXPRESSPAY_CARD_DESCRIPTION");

	$url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

	$arPSCorrespondence = array(
		"EXPRESSPAY_CARD_IS_TEST_API" => array(
			"SORT" => 10,
			"NAME" => GetMessage("EXPRESSPAY_CARD_IS_TEST_API_NAME"),
			"DESCR"	=> GetMessage("EXPRESSPAY_CARD_IS_TEST_API_DESCR"),
			"INPUT" => array(
				'TYPE' => 'Y/N'
			),
			'DEFAULT' => array(
				"PROVIDER_VALUE" => "N",
				"PROVIDER_KEY" => "INPUT"
			)
		),
		"EXPRESSPAY_CARD_TOKEN"	=> array(
			"SORT" => 20,
			"NAME"	=> GetMessage("EXPRESSPAY_CARD_TOKEN_NAME"),
			"DESCR"	=> GetMessage("EXPRESSPAY_CARD_TOKEN_DESCR"),
			"VALUE"	=> "",
			"TYPE"	=> ""
		),
		"EXPRESSPAY_CARD_SERVICE_ID"	=> array(
			"SORT" => 30,
			"NAME"	=> GetMessage("EXPRESSPAY_CARD_SERVICE_ID_NAME"),
			"DESCR"	=> GetMessage("EXPRESSPAY_CARD_SERVICE_ID_DESCR"),
			"VALUE"	=> "",
			"TYPE"	=> ""
		),
		"EXPRESSPAY_CARD_SECRET_WORD"	=> array(
			"SORT" => 40,
			"NAME"	=> GetMessage("EXPRESSPAY_CARD_SECRET_WORD_NAME"),
			"DESCR"	=> GetMessage("EXPRESSPAY_CARD_SECRET_WORD_DESCR"),
			"DEFAULT" => array(
				"PROVIDER_VALUE" => "",
				"PROVIDER_KEY" => "VALUE"
			)
		),
		"EXPRESSPAY_CARD_NOTIFICATION_URL"	=> array(
			"SORT" => 45,
			"NAME"	=> GetMessage("EXPRESSPAY_CARD_NOTIFICATION_URL_NAME"),
			"DESCR"	=> GetMessage("EXPRESSPAY_CARD_NOTIFICATION_URL_DESCR"),
			"DEFAULT" => array(
				"PROVIDER_VALUE" => $url. "/bitrix/tools/expresspay_notify/expresspay_notify.php",
				"PROVIDER_KEY" => "VALUE"
			)
		),
		"IS_USE_SIGNATURE_FROM_NOTIFICATION" => array(
			"SORT" => 50,
			"NAME" => GetMessage("IS_USE_SIGNATURE_FROM_NOTIFICATION_NAME"),
			"DESCR"	=> GetMessage("IS_USE_SIGNATURE_FROM_NOTIFICATION_DESCR"),
			"INPUT" => array(
				'TYPE' => 'Y/N'
			),
			'DEFAULT' => array(
				"PROVIDER_VALUE" => "N",
				"PROVIDER_KEY" => "INPUT"
			)
		),
		"SECRET_WORD_FROM_NOTIFICATION"	=> array(
			"SORT" => 55,
			"NAME"	=> GetMessage("SECRET_WORD_FROM_NOTIFICATION_NAME"),
			"DESCR"	=> GetMessage("SECRET_WORD_FROM_NOTIFICATION_DESCR"),
			"DEFAULT" => array(
				"PROVIDER_VALUE" => "",
				"PROVIDER_KEY" => "VALUE"
			)
		),
		"EXPRESSPAY_CARD_INFO_TEMPLATE"	=> array(
			"SORT" => 60,
			"NAME"	=> GetMessage("EXPRESSPAY_CARD_INFO_TEMPLATE_NAME"),
			"DESCR"	=> GetMessage("EXPRESSPAY_CARD_INFO_TEMPLATE_DESCR"),
			"DEFAULT" => array(
				"PROVIDER_VALUE" => GetMessage("EXPRESSPAY_CARD_INFO_TEMPLATE_PROVIDER_VALUE"),
				"PROVIDER_KEY" => "VALUE"
			)
		),
	);
?>