<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\PaySystem,
	Bitrix\Main\Request,
	Bitrix\Sale\Payment,
	Bitrix\Sale\PaySystem\ServiceResult,
	Bitrix\Sale\PaymentCollection,
	Bitrix\Sale\PriceMaths;

Loc::loadMessages(__FILE__);

/**
 * Class ExpressPay_CardHandler
 * @package Sale\Handlers\PaySystem
 */
class ExpressPay_CardHandler extends PaySystem\ServiceHandler
{
	private const API_URL = 'https://api.express-pay.by/v1/';
	private const TEST_API_URL = 'https://sandbox-api.express-pay.by/v1/';

	private const SEND_METHOD_HTTP_POST = "POST";
	private const SEND_METHOD_HTTP_GET = "GET";

	private const CHECKOUT_TEMPLATE = "checkout";
	private const ERROR_TEMPLATE = "error";

	/**
	 * 
	 * Главная функция в обработчике, в ней можно, например, 
	 * добавить к параметрам обработчика, которые задаются 
	 * в административной части, какие-то дополнительные, 
	 * и вызвать шаблон, который должен находится в подпапке 
	 * template (перечень параметров обработчика платежей, 
	 * задаваемых в админке, задается в файле 
	 * .description.php, его структуру вы можете также изучить 
	 * по исходным кодам системных обработчиков). 
	 * Сам шаблон можно скопировать в шаблон сайта в 
	 * подпапку payment/название_папки_обработчика/template/.
	 * 
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function initiatePay(Payment $payment, Request $request = null): ServiceResult
	{
			if ($_REQUEST['result'] == 'success'){

				$result = new ServiceResult();

				$this->setExtraParams($this->getTemplateParams($payment));
	
				$showTemplateResult = $this->showTemplate($payment, self::CHECKOUT_TEMPLATE);
	
				if ($showTemplateResult->isSuccess()) {
					$result->setTemplate($showTemplateResult->getTemplate());
				} else {
					$result->addErrors($showTemplateResult->getErrors());
				}
	
				return $result;
			} else if ($_REQUEST['result'] == 'fail') {

				$result = new ServiceResult();
	
				$this->setExtraParams(['payment_error' => true]);
	
				$showTemplateResult = $this->showTemplate($payment, self::ERROR_TEMPLATE);
	
				if ($showTemplateResult->isSuccess()) {
					$result->setTemplate($showTemplateResult->getTemplate());
				} else {
					$result->addErrors($showTemplateResult->getErrors());
				}
	
				return $result;
			} else {
			return $this->sendRequest($payment);
		}
	}

	/**
	 * 
	 * Отправка POST-запроса и обработка ответа полученного от API
	 * 
	 * @param Payment $payment Объект платежа
	 * 
	 * @return ServiceResult $result Обработанный ответ
	 * 
	 */
	private function sendRequest(Payment $payment): ServiceResult{

		$request_params = $this->getInvoiceParam($payment);

		if ($this->isTestMode($payment)) {
			$url = self::TEST_API_URL . "web_cardinvoices";
		} else {
			$url = self::API_URL . "web_cardinvoices";
		}

		$result = $this->send(self::SEND_METHOD_HTTP_POST, $url, $request_params);

		if (isset($result->getData()['Errors'])) {
			PaySystem\Logger::addDebugInfo(__CLASS__ . ': API errors: ' . print_r($result->getData()['Errors'], 1));
			$this->setExtraParams(['message' => Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_ERROR_GENERAL')]);
		} else if (isset($result->getData()['FormUrl'])) {
			LocalRedirect($result->getData()['FormUrl'], true);
		}

		$showTemplateResult = $this->showTemplate($payment, self::ERROR_TEMPLATE);

		if ($showTemplateResult->isSuccess()) {
			$result->setTemplate($showTemplateResult->getTemplate());
		} else {
			$result->addErrors($showTemplateResult->getErrors());
		}
		
		return $result;
	}

	/**
	 * 
	 * Функция getIndicativeFields должна вернуть массив полей, 
	 * по которым проверяется принадлежность информации при возврате 
	 * к данному обработчику, при этом функция может вернуть 
	 * как ассоциативный массив (т.е. с символьными ключами), 
	 * при этом проверка будет производится по значениям полей, 
	 * так и неассоциативный - при этом проверка будет производится 
	 * только по наличию полей в $request.
	 * 
	 */
	public static function getIndicativeFields()
	{
    	return array('Data');
	}

	/**
	 * Формирование массива значений для шаблона
	 * 
	 * @param Payment $payment   Объект платежа
	 * 
	 * @return array  $params Массив значений
	 * 
	 */
	private function getTemplateParams(Payment $payment): array
	{

		$params = [
			'sum' => (string)(PriceMaths::roundPrecision($payment->getSum())),
			'currency' => $payment->getField('CURRENCY'),
		];

		return $params;
	}


	/**
	 * 
	 * Получаем параметры для заполнения формы 
	 * 
	 * @return array $request_params Параметры запроса 
	 * 
	 */
	private function getInvoiceParam(Payment $payment)
	{
		$collection = $payment->getCollection();
		$order = $collection->getOrder();
		$userEmail = $order->getPropertyCollection()->getUserEmail();

		if ($this->is_test == "Y")
			$order_id = 100;
		else
			$order_id = IntVal($payment->getId());

		$out_summ = number_format(floatval($payment->getSum()), 2, ',', '');

		$serviceId = $this->getBusinessValue($payment, 'CARD_SERVICE_ID');
		$info = str_replace('##ORDER_ID##', $order_id, $this->getBusinessValue($payment, 'CARD_INFO_TEMPLATE'));

		// Доверенный хост только из настроек; никакого fallback на заголовок Host
		$currentHost = trim((string)\Bitrix\Main\Config\Option::get('main', 'server_name'));

		$returnParams = ['result' => 'success'];
		$failParams = ['result' => 'fail'];

		if ($currentHost === '' || !preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $currentHost)) {
			// HTTP_HOST подконтролен клиенту — используем относительный URL
			PaySystem\Logger::addDebugInfo(__CLASS__ . ': main:server_name is not configured — using relative return URL');

			$path = '/bitrix/tools/sale_ps_result.php';
			$returnUrl = $path . '?' . http_build_query($returnParams);
			$failUrl   = $path . '?' . http_build_query($failParams);
		} else {
			$protocol = 'https://';   // ← принудительно https

			// Сверка с allowlist доменов магазина
			$trustedHosts = array_filter(array_map('trim', explode(",",
				(string)\Bitrix\Main\Config\Option::get("main", "trusted_hosts"))));

			if ($trustedHosts && !in_array(strtolower($currentHost), array_map('strtolower', $trustedHosts), true)) {
				throw new Main\SystemException('Untrusted server_name for payment return URL');
			}

			$requestHost = $_SERVER['HTTP_HOST'] ?? '';
			if ($requestHost !== '' && strcasecmp($requestHost, $currentHost) !== 0) {
				PaySystem\Logger::addDebugInfo(sprintf(
					'%s: Host mismatch. HTTP_HOST=%s, trusted_host=%s',
					__CLASS__, $requestHost, $currentHost
				));
			}

			$path = '/bitrix/tools/sale_ps_result.php';
			$returnUrl = $protocol . $currentHost . $path . '?' . http_build_query($returnParams);
			$failUrl   = $protocol . $currentHost . $path . '?' . http_build_query($failParams);
		}

		$request_params = array(
			'ServiceId'         => $serviceId,
			'AccountNo'         => $order_id,
			'Amount'            => $out_summ,
			'Currency'          => $this->getCurrencyISOCode($payment->getField('CURRENCY')),
			'ReturnType'        => 'json',
			'ReturnUrl'         => $returnUrl,
			'FailUrl'           => $failUrl,
			'Expiration'        => '',
			'Info'              => $info,
		);

		$request_params['Signature'] = $this->computeSignature($request_params, $payment);

		return $request_params;
	}

	/**
	 * Формирование цифровой подписи
	 * 
	 * @param array  $request_params Параметры запроса
	 * @param string $method Метод API
	 * 
	 * @return string $hash Полученный хеш
	 */
	private function computeSignature($request_params, Payment $payment, $method = 'add_invoice')
	{
		$secret_word = trim($this->getSecretWord($payment));
		$normalized_params = array_change_key_case($request_params, CASE_LOWER);
		$api_method = array( 
			'add_invoice' => array(
								"serviceid",
								"accountno",
								"expiration",
								"amount",
								"currency",
								"info",
								"returnurl",
								"failurl",
								"language",
								"sessiontimeoutsecs",
								"expirationdate",
								"returntype"),
			'add_invoice_return' => array(
								"accountno"
			)
		);

		$result = $this->getToken($payment);

		foreach ($api_method[$method] as $item)
			$result .= (isset($normalized_params[$item])) ? $normalized_params[$item] : '';

		$hash = strtoupper(hash_hmac('sha1', $result, $secret_word));

		return $hash;
	}

	/**
	 * @param string $method HTTP-метод
	 * @param string $url Адрес запроса
	 * @param array $params Параметры запроса
	 * @param array $headers Заголовки запроса
	 * @return ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectException
	 */
	private function send(string $method, string $url, array $params = [], array $headers = []): ServiceResult
	{
		$result = new ServiceResult();

		$httpClient = new HttpClient();
		foreach ($headers as $name => $value) {
			$httpClient->setHeader($name, $value);
		}

		if ($method === self::SEND_METHOD_HTTP_GET) {
			$response = $httpClient->get($url);
		} else {
			PaySystem\Logger::addDebugInfo(__CLASS__ . ': request data: ' . print_r([
				'ServiceId' => $params['ServiceId'] ?? null,
				'AccountNo' => $params['AccountNo'] ?? null,
				'Amount'    => $params['Amount'] ?? null,
				'Currency'  => $params['Currency'] ?? null,
			], 1));

			$response = $httpClient->post($url, $params);
		}

		if ($response === false) {
			$errors = $httpClient->getError();
			foreach ($errors as $code => $message) {
				$result->addError(PaySystem\Error::create($message, $code));
			}

			return $result;
		}

		PaySystem\Logger::addDebugInfo(__CLASS__ . ': response received, length: ' . strlen((string)$response));

		$response = static::decode($response);
		if ($response) {
			$result->setData($response);
		} else {
			$result->addError(PaySystem\Error::create($response));
		}

		return $result;
	}

	/**
	 * Получение списка валют
	 * 
	 * @return array|string[]
	 */
	public function getCurrencyList(): array
	{
		return ['BYN','RUB', 'EUR', 'USD'];
	}

	/**
	 * Поиск необходимого кода валюты по ISO 4217
	 * 
	 * @param string $code Буквенный код валюты
	 * 
	 * @return int Цифровой код валюты 
	 * 
	 */
	private function getCurrencyISOCode($code)
	{
		$currency_code = [
			 933 => 'BYN',
			 643 => 'RUB',
			 978 => 'EUR',
			 840 => 'USD',
		];

		return array_search($code, $currency_code);
	}

	/**
	 * Создание оплаты
	 * 
	 * @param Payment $payment
	 * @param Request $request
	 * @return ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectException
	 */
	public function processRequest(Payment $payment, Request $request): ServiceResult
	{
		$result = new ServiceResult();

		$rawData   = (string)$request->getPost("Data");
		$signature = trim((string)$request->getPost("Signature"));

		$data = static::decode($rawData);

		if (!is_array($data)) {
			$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_INVALID_NOTIFICATION_DATA')));
			return $result;
		}

		$cmdType = filter_var($data['CmdType'] ?? null, FILTER_VALIDATE_INT);
		$status  = filter_var($data['Status'] ?? null, FILTER_VALIDATE_INT);

		if ($cmdType === false || $status === false) {
			$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_INVALID_NOTIFICATION_DATA')));
			return $result;
		}

		if ($cmdType === 3 && ($status === 3 || $status === 6)) {

			// Подпись проверяется ВСЕГДА — fail-closed
			$computedSignature = $this->computeNotifSignature($rawData);

			if ($computedSignature === null) {
				PaySystem\Logger::addDebugInfo(__CLASS__ . ': ' . Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_EMPTY_SECRET_CONFIG_ERROR'));
				$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_EMPTY_SECRET_CONFIG_ERROR')));
				return $result;
			}

			if ($signature === '' || !hash_equals($computedSignature, strtoupper($signature))) {
				PaySystem\Logger::addDebugInfo(sprintf(
					'%s: rejected unsigned/invalid notification for payment %d',
					__CLASS__, $payment->getId()
				));
				$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_WRONG_SIGNATURE')));
				return $result;
			}

			$notificationAccountNo = filter_var($data['AccountNo'] ?? null, FILTER_VALIDATE_INT);
			if ($notificationAccountNo === false || $notificationAccountNo !== $payment->getId()) {
				PaySystem\Logger::addDebugInfo(sprintf(
					'%s: AccountNo mismatch. Notification=%s, Payment=%d',
					__CLASS__, var_export($data['AccountNo'] ?? null, true), $payment->getId()
				));
				$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_MISMATCH')));
				return $result;
			}

			if ($payment->isPaid()) {
				$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_ALREADY_PAID')));
				return $result;
			}

			$notificationAmount = PriceMaths::roundPrecision((float)str_replace(',', '.', $data['Amount']));
			$expectedSum = PriceMaths::roundPrecision($payment->getSum());
			if ($notificationAmount !== $expectedSum) {
				$result->addError(PaySystem\Error::create(Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_AMOUNT_MISMATCH')));
				return $result;
			}

			$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
			$result->setPsData($this->getPaymentField($data));
		}

		return $result;
	}

	/**
	 * 
	 * Формирование полей опалаты
	 * 
	 * @param array $data Параметры уведомления
	 * 
	 * @return array $fields Поля опалаты
	 * 
	 */
	private function getPaymentField($data): array
	{
		$description = Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_DESCRIPTION', [
			'#ID#' => $data['AccountNo'],
			'#Payer#' => $data['Payer'],
		]);

		$fields = [
			"PS_STATUS" => "Y",
			"PS_STATUS_CODE" => $data['Status'],
			"PS_STATUS_DESCRIPTION" => $data['Status'] == 6 ? Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_STATUS_DESCRIPTION_CARD') : Loc::getMessage('SALE_HPS_EXPRESSPAY_CARD_PAYMENT_STATUS_DESCRIPTION'),
			"PS_STATUS_MESSAGE" => $description,
			"PS_SUM" => $data['Amount'],
			"PS_CURRENCY" => 'BYN',
			"PS_RESPONSE_DATE" => new \Bitrix\Main\Type\DateTime(),
			"PAY_VOUCHER_NUM" => $data['AccountNo'],
			"PAY_VOUCHER_DATE" => new \Bitrix\Main\Type\Date()
		];
		
		return $fields;
	}

	/**
	 * Формирование цифровой подписи для уведомления
	 * 
	 * @param string $json Полученное уведомление
	 * 
	 * @return string $hash Полученный хеш
	 */
	private function computeNotifSignature($json)
	{
		$secretWord = $this->getSecretWordForNotif();
		if (empty($secretWord)) {
			return null;
		}
		return strtoupper(hash_hmac('sha1', $json, $secretWord));
	}

	/**
	 * 
	 * Получение ID оплаты при получении уведомления 
	 * 
	 * @param Request $request
	 * @return bool|int|mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		$data = $request->getPost("Data");
		$data = static::decode($data);

		if (isset($data)) {
				return (int)$data['AccountNo'];
			}
		return false;
	}

	/**
	 * @param Payment $payment
	 * @return mixed|string
	 */
	private function getToken(Payment $payment)
	{
		return $this->getBusinessValue($payment, 'CARD_TOKEN');
	}

	/**
	 * @param Payment $payment
	 * @return mixed|string
	 */
	private function getSecretWord(Payment $payment)
	{
		return $this->getBusinessValue($payment, 'CARD_SECRET_WORD');
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = null): bool
	{
		return ($this->getBusinessValue($payment, 'CARD_IS_TEST_API') === 'Y');
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	private function getSecretWordForNotif(Payment $payment = null)
	{
		return $this->getBusinessValue($payment, 'CARD_SECRET_WORD_FROM_NOTIFICATION');
	}

	/**
	 * @param string $data
	 * @return mixed
	 */
	private static function decode($data)
	{
		try {
			return Main\Web\Json::decode($data);
		} catch (Main\ArgumentException $exception) {
			return false;
		}
	}
}
