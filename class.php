<?
if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true ) {
	die();
}

use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Error;
use \Bitrix\Main\ErrorCollection;
use \Bitrix\Catalog\Product\Basket;

Loc::loadMessages(__FILE__);


class iplogicSaleQuickOrder extends \CBitrixComponent
	implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{

	/** @var ErrorCollection */
	protected $errorCollection;

	protected $order;
	protected $req;
	protected $siteId;
	protected $arFields;

	function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new ErrorCollection();

		if( !Loader::includeModule('sale') ) {
			$this->errorCollection[] = new Error('No sale module');
		};

		if( !Loader::includeModule('catalog') ) {
			$this->errorCollection[] = new Error('No catalog module');
		};

		$this->siteId = \Bitrix\Main\Context::getCurrent()->getSite();
	}

	public function configureActions()
	{
		//fill it, or use default
		return [];
	}

	public function onPrepareComponentParams($arParams)
	{
		if(
			isset($arParams['IS_AJAX'])
			&& ($arParams['IS_AJAX'] == 'Y' || $arParams['IS_AJAX'] == 'N')
		) {
			$arParams['IS_AJAX'] = $arParams['IS_AJAX'] == 'Y';
		}
		else {
			if(
				isset($this->request['is_ajax'])
				&& ($this->request['is_ajax'] == 'Y' || $this->request['is_ajax'] == 'N')
			) {
				$arParams['IS_AJAX'] = $this->request['is_ajax'] == 'Y';
			}
			else {
				$arParams['IS_AJAX'] = false;
			}
		}

		$arParams['ACTION'] = $this->getParam('ACTION', $arParams);

		return $arParams;
	}

	protected function getParam($name, $arParams)
	{
		if( isset($this->request[strtolower($name)]) && strlen($this->request[strtolower($name)]) > 0 ) {
			return strval($this->request[strtolower($name)]);
		}
		else {
			if( isset($arParams[strtoupper($name)]) && strlen($arParams[strtoupper($name)]) > 0 ) {
				return strval($arParams[strtoupper($name)]);
			}
			else {
				return '';
			}
		}
	}

	function executeComponent()
	{
		global $APPLICATION;

		$this->arFields = $this->getFields();

		$this->order = \Bitrix\Sale\Order::create($this->arParams["SITE_ID"]);

		if(!is_array($this->request["form_data"])) {
			if(strlen($this->request["form_data"])) {
				$this->req["form_data"] = [];
				$pares = explode("&", $this->request["form_data"]);
				foreach($pares as $pare) {
					$val = explode("=", $pare);
					$this->req["form_data"][$val[0]] = urldecode($val[1]);
				}
			}
		}
		else {
			$this->req["form_data"] = $this->request["form_data"];
		}

		if( $this->arParams['IS_AJAX'] ) {
			$APPLICATION->RestartBuffer();
		}

		if( !empty($this->arParams['ACTION']) ) {
			if( is_callable([$this, $this->arParams['ACTION'] . "Action"]) ) {
				try {
					call_user_func([$this, $this->arParams['ACTION'] . "Action"]);
				} catch( \Exception $e ) {
					$this->setError($e->getMessage());
				}
			}
		}

		if( count($this->getErrors()) ) {
			$this->arResponse['errors'] = $this->getErrors();
		}

		if( $this->arParams['IS_AJAX'] ) {
			header('Content-Type: application/json');
			echo json_encode($this->arResponse);
			$APPLICATION->FinalActions();
			die();
		}
		else {
			$this->prepareResult();
			$this->includeComponentTemplate();
		}
	}

	protected function prepareResult()
	{
		$this->arResult['FIELDS'] = $this->arFields;
		if( $this->arParams["USE_USER_CONSENT"] != "NO" ) {
			if( $this->arParams["USE_USER_CONSENT"] == "CUSTOM" ) {
				$this->arResult["CONSENT_TEXT"] = $this->arParams["USER_CONSENT_TEXT"];
			}
			if( $this->arParams["USE_USER_CONSENT"] == "BITRIX" ) {
				$obAgreement = new \Bitrix\Main\UserConsent\Agreement($this->arParams["BITRIX_USER_CONSENTS"]);
				$arAgreement = $obAgreement->getData();
				$this->arResult["CONSENT_ACTIVE"] = $arAgreement["ACTIVE"];
				if( $arAgreement["USE_URL"] == "Y" ) {
					if( substr_count($arAgreement["LABEL_TEXT"], '%') >= 2 ) {
						$ar = explode("%", $arAgreement["LABEL_TEXT"]);
						$this->arResult["CONSENT_TEXT"] =
							$ar[0] . "<a href='" . $arAgreement["URL"] . "'>" . $ar[1] . "</a>" . $ar[2];
					}
					else {
						$this->arResult["CONSENT_TEXT"] =
							"<a href='" . $arAgreement["URL"] . "'>" . $arAgreement["LABEL_TEXT"] . "</a>";
					}
				}
				else {
					$this->arResult["CONSENT_TEXT"] = $arAgreement["LABEL_TEXT"];
				}
			}
		}
	}

	protected function getFields()
	{
		$order = \Bitrix\Sale\Order::create($this->arParams["SITE_ID"]);
		$arProperties = [];
		$propertyCollection = $order->getPropertyCollection();
		$propertyPT = $this->arParams["PERSON_TYPE_ID"];
		$properties = $propertyCollection->getItemsByFilter(
			function($propertyValue) use ($propertyPT) {
				return $propertyValue->getPersonTypeId() == $propertyPT;
			}
		);
		foreach( $properties as $property ) {
			$code = $property->getField('CODE');
			if( in_array($code, $this->arParams["FORM_FIELDS"]) ) {
				$arProperties[$code] = $property->getProperty();
				$arProperties[$code]["REQUIRED"] = (in_array($code, $this->arParams["FORM_FIELDS_REQ"]) ? "Y" : "N");
			}
		}
		return $arProperties;
	}

	protected function saveAction()
	{
		$this->arResponse["request"] = $this->req;
		$this->validateFields();
		if( count($this->getErrors()) ) {
			return;
		}
		$this->createVirtualOrder();
		if( count($this->getErrors()) ) {
			return;
		}

		if( $this->arParams["USE_USER_CONSENT"] == "BITRIX" ) {
			if( $this->req["form_data"]["USER_CONSENT"] == "Y" ) {
				\Bitrix\Main\UserConsent\Consent::addByContext(
					$this->arParams["BITRIX_USER_CONSENTS"]
				);
			}
		}

		$this->order->doFinalAction(true);
		$result = $this->order->save();
		if (!$result->isSuccess()) {
			$this->setError(Loc::getMessage("IPL_SQO_ORDER_SAVE_ERROR") . ": " . $result->getErrorMessages());
		}
		else {
			$orderId = $this->order->getId();
		}
		if( !count($this->getErrors()) ) {
			$this->arResponse["success"] = "Y";
			$this->arResponse["order_id"] = $orderId;
		}
	}

	protected function validateFields()
	{
		if( $this->req["form_data"]["PRODUCT_ID"] < 1 ) {
			$this->setError(Loc::getMessage("IPL_SQO_NO_PRODUCT_ID"));
		}
		foreach( $this->arFields as $arField ) {
			if( $arField["REQUIRED"] == "Y" ) {
				$value = $this->req["form_data"][$arField["CODE"]];
				if( $arField["TYPE"] == "Y/N" ) {
					if( !isset($this->req["form_data"][$arField["CODE"]]) || $value == "N" ) {
						$this->setError(Loc::getMessage("IPL_SQO_REQUIRED_FIELD", ["#NAME#" => $arField["NAME"]]));
					}
				}
				else {
					if( $value == "" ) {
						$this->setError(Loc::getMessage("IPL_SQO_REQUIRED_FIELD", ["#NAME#" => $arField["NAME"]]));
					}
					else {
						if( $arField["IS_PHONE"] == "Y" ) {
							if(!preg_match("/^\\+?[1-9][0-9\ \-\(\)]{4,17}$/", $value)) {
								$this->setError(
									Loc::getMessage("IPL_SQO_WRONG_FORMAT", ["#NAME#" => $arField["NAME"]])
								);
							}
						}
						if( $arField["IS_EMAIL"] == "Y" ) {
							if( !filter_var($value, FILTER_VALIDATE_EMAIL) ) {
								$this->setError(
									Loc::getMessage("IPL_SQO_WRONG_FORMAT", ["#NAME#" => $arField["NAME"]])
								);
							}
						}
					}
				}
			}
		}
		if( $this->arParams["ADD_COMMENT"] == "Y" ) {
			if( $this->arParams["COMMENT_REQUIRED"] == "Y" && $this->req["form_data"]["USER_COMMENT"] == "" ) {
				$this->setError(
					Loc::getMessage("IPL_SQO_REQUIRED_FIELD", ["#NAME#" => Loc::getMessage("IPL_SQO_COMMENT")])
				);
			}
		}
		if( $this->arParams["USE_USER_CONSENT"] != "NO" ) {
			if( !isset($this->req["form_data"]["USER_CONSENT"]) || $this->req["form_data"]["USER_CONSENT"] == "N" ) {
				$this->setError(
					Loc::getMessage("IPL_SQO_REQUIRED_FIELD", ["#NAME#" => Loc::getMessage("IPL_SQO_USER_CONSENT")])
				);
			}
		}
	}

	protected function createVirtualOrder()
	{
		try {
			$this->order = \Bitrix\Sale\Order::create($this->siteId, \Bitrix\Sale\Fuser::getId());
			$this->order->setPersonTypeId($this->arParams['PERSON_TYPE_ID']);
			$this->setBasket();
			$this->setOrderProps();
			$this->setDelivery();
			$this->setPaySystem();
		} catch( \Exception $e ) {
			$this->setError($e->getMessage());
		}
	}

	protected function setBasket()
	{
		$basket = \Bitrix\Sale\Basket::create($this->siteId);
		$item = $basket->createItem('catalog', $this->req["form_data"]["PRODUCT_ID"]);
		$item->setFields(
			[
				'QUANTITY'               => 1,
				'CURRENCY'               => $this->arParams['CURRENCY_ID'],
				'LID'                    => $this->siteId,
				'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
			]
		);
		$this->order->setBasket($basket);
	}

	protected function setOrderProps()
	{
		foreach( $propertyCollection = $this->order->getPropertyCollection() as $index => $prop ) {
			/** @var \Bitrix\Sale\PropertyValue $prop */
			$value = '';

			if( array_key_exists($prop->getField('CODE'), $this->arFields) ) {
				$value = $this->req["form_data"][$prop->getField('CODE')];
			}
			if( !empty($value) ) {
				$prop->setValue($value);
			}
		}

		if( $this->arParams["ADD_COMMENT"] == "Y" ) {
			$this->order->setField('USER_DESCRIPTION', $this->req["form_data"]["USER_COMMENT"]);
		}

		$this->order->setField('COMMENTS', Loc::getMessage("IPL_SQO_QUICK_ORDER"));
	}

	protected function setDelivery()
	{
		/* @var $shipmentCollection \Bitrix\Sale\ShipmentCollection */
		$shipmentCollection = $this->order->getShipmentCollection();
		$service = \Bitrix\Sale\Delivery\Services\Manager::getObjectById(
			intval($this->arParams["DELIVERY_SERVICE_ID"])
		);
		$shipment = $shipmentCollection->createItem($service);
		$shipment->setFields(
			[
				'DELIVERY_ID'    => $service->getId(),
				'DELIVERY_NAME'  => $service->getName(),
				'ALLOW_DELIVERY' => 'Y',
				'PRICE_DELIVERY' => $service->calculate($shipment)->getPrice(),
			]
		);

		/** @var $shipmentItemCollection \Bitrix\Sale\ShipmentItemCollection */
		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$shipment->setField('CURRENCY', $this->order->getCurrency());
		foreach( $this->order->getBasket()->getOrderableItems() as $item ) {
			/**
			 * @var $item \Bitrix\Sale\BasketItem
			 * @var $shipmentItem \Bitrix\Sale\ShipmentItem
			 * @var $item \Bitrix\Sale\BasketItem
			 */
			$shipmentItem = $shipmentItemCollection->createItem($item);
			$shipmentItem->setQuantity($item->getQuantity());
		}
	}

	protected function setPaySystem()
	{
		$paymentCollection = $this->order->getPaymentCollection();
		$payment = $paymentCollection->createItem();
		$paySystemService = \Bitrix\Sale\PaySystem\Manager::getObjectById(intval($this->arParams["PAY_SYSTEM_ID"]));
		$payment->setFields(
			[
				'PAY_SYSTEM_ID'   => $paySystemService->getField("PAY_SYSTEM_ID"),
				'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
				'SUM'             => $this->order->getPrice(),
				'CURRENCY'        => $this->order->getCurrency(),
			]
		);
	}

	protected function setError($str, $code = 0)
	{
		$error = new \Bitrix\Main\Error($str, $code, "");
		$this->errorCollection->setError($error);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

}