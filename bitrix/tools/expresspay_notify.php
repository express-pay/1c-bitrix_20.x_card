<?
use \Bitrix\Sale\Order;

define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define("DisableEventsCheck", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
CModule::IncludeModule("sale");

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	echo('Test OK');
}

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$json = $_POST['Data'];
	$signature = $_POST['Signature'];
	
	// Преобразуем из JSON в Object
	$data = json_decode($json);
	
	if($orderDataArray = CSaleOrder::GetByID(intval($data->AccountNo)))
	{
		// инициализация переменных платежной системы
		CSalePaySystemAction::InitParamArrays($orderDataArray, $orderDataArray["ID"]);
	
		// Использование цифровой подписи указывается в настройках личного кабинета
		$isUseSignature = CSalePaySystemAction::GetParamValue("IS_USE_SIGNATURE_FROM_NOTIFICATION");
		
		// Проверяем использование цифровой подписи
		if($isUseSignature == 'Y')
		{
			// Секретное слово указывается в настройках личного кабинета
			$secretWord = CSalePaySystemAction::GetParamValue("SECRET_WORD_FROM_NOTIFICATION");
		
			// Проверяем цифровую подпись
			if($signature == computeSignature($json, $secretWord))
			{			
				updateOrder($data);
				
				$status = 'OK | payment received';
				echo($status);
				header("HTTP/1.0 200 OK");
			}
			else
			{			
				$status = 'FAILED | wrong notify signature'; 
				echo($status);
				header("HTTP/1.0 400 Bad Request");
			}
		}
		else
		{
			updateOrder($data);

			$status = 'OK | payment received';
			echo($status);
			header("HTTP/1.0 200 OK");
		}
	}
	else
	{
		$status = 'FAILED | ID заказа неизвестен'; 
		echo($status);
		header("HTTP/1.0 200 Bad Request");
	}
}

function computeSignature($json, $secretWord)
{
    $hash = NULL;
    
	$secretWord = trim($secretWord);
	
    if (empty($secretWord))
		$hash = strtoupper(hash_hmac('sha1', $json, ""));
    else
        $hash = strtoupper(hash_hmac('sha1', $json, $secretWord));
    return $hash;
}

// обновление статуса заказа
function updateOrder($data)
{
	// Изменился статус счета
	if($data->CmdType == '3')
	{	
		// Счет оплачен
		if($data->Status == '3')
		{		
			// получение заказа по номеру лицевого счета
			$orderDataArray = CSaleOrder::GetByID(intval($data->AccountNo));

			// заказ существует
			if(isset($orderDataArray))
			{
				CSalePaySystemAction::InitParamArrays($orderDataArray, $orderDataArray["ID"]);

				$arFields = array(
					"PS_STATUS" => "Y",
					"PS_STATUS_DESCRIPTION" => "",
					"PS_SUM" => $data->Amount,
					"PS_RESPONSE_DATE" => new \Bitrix\Main\Type\DateTime(),
					"USER_ID" => $orderDataArray["USER_ID"]
				  );

				  if ($orderDataArray["PAYED"] != "Y")
				  {
					CSaleOrder::PayOrder($orderDataArray["ID"], "Y", True, True, 0, $arFields);
					CSaleOrder::StatusOrder($orderDataArray["ID"], "P");
				  }
			}		
		}
		// Счет отменён
			if($data->Status == '5') {	
				
			// получение заказа по номеру лицевого счета
			$order = CSaleOrder::GetByID($data->AccountNo);
		
					// заказ существует
					if(isset($order)) {
						CSalePaySystemAction::InitParamArrays($order, $order["ID"]);
						
						// помечаем заказ как отменённый
						$arFields = array(
							"CANCELED " => "Y",
							"STATUS_ID" => "F",
						);
						CSaleOrder::Update($order["ID"], $arFields);
						CSaleOrder::CancelOrder($order["ID"], "Y", "fdfsfsdfdfs");
					}		
				}
	}
}
?>