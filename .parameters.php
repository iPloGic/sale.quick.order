<?
if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true ) {
	die();
}

/** @var array $arCurrentValues */

use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

if( !Loader::includeModule('iblock') ) {
	return;
}
if( !Loader::includeModule('catalog') ) {
	return;
}
if( !Loader::includeModule('sale') ) {
	return;
}

// SITES
$arSites = [];
$res = \Bitrix\Main\SiteTable::getList(
	[
		"order"  => ["SORT" => "ASC"],
		"filter" => ["ACTIVE" => "Y"],
	]
);
while( $arRes = $res->Fetch() ) {
	$arSites[$arRes["LID"]] = '[' . $arRes["LID"] . ']' . $arRes["SITE_NAME"];
}
$arSiteDefault = array_key_first($arSites);
if( $arCurrentValues["SITE_ID"] == "" ) {
	$arCurrentValues["SITE_ID"] = $arSiteDefault;
}
$order = \Bitrix\Sale\Order::create($arCurrentValues["SITE_ID"]);


// IBLOCK TYPES
$arIBlocksTypes = ["-" => Loc::getMessage("IPL_SQO_NOT_CHOSEN")];
$arIBlocksTypes = $arIBlocksTypes + CIBlockParameters::GetIBlockTypes();


// IBLOCKS
$arIBlocks = ["-" => Loc::getMessage("IPL_SQO_NOT_CHOSEN")];
if( $arCurrentValues["IBLOCK_TYPE"] != "-" && $arCurrentValues["IBLOCK_TYPE"] != "" ) {
	$arIBlocks = [];
	$res = \Bitrix\Iblock\IblockTable::getList(
		[
			"order"  => ["SORT" => "ASC"],
			"filter" => ["LID" => $arCurrentValues["SITE_ID"], "IBLOCK_TYPE_ID" => $arCurrentValues["IBLOCK_TYPE"]],
		]
	);
	while( $arRes = $res->Fetch() ) {
		$arIBlocks[$arRes["ID"]] = '[' . $arRes["ID"] . ']' . $arRes["NAME"];
	}
}


// PERSON TYPES
$arPersonTypes = [];
$res = Bitrix\Sale\Internals\PersonTypeTable::getList(
	[
		"order"  => ["SORT" => "ASC"],
		"filter" => ["LID" => $arCurrentValues["SITE_ID"], "ACTIVE" => "Y"],
	]
);
while( $arRes = $res->Fetch() ) {
	$arPersonTypes[$arRes["ID"]] = '[' . $arRes["ID"] . ']' . $arRes["NAME"];
}
if( $arCurrentValues["PERSON_TYPE_ID"] == "" ) {
	$arCurrentValues["PERSON_TYPE_ID"] = array_key_first($arPersonTypes);
}


// PAY SYSTEMS
$arPaySystems = [];
$rsPaySystem = \Bitrix\Sale\Internals\PaySystemActionTable::getList(
	[
		'filter' => ['ACTIVE' => 'Y'],
	]
);
while( $arRes = $rsPaySystem->fetch() ) {
	$arPaySystems[$arRes["ID"]] = '[' . $arRes["ID"] . ']' . $arRes["NAME"];
}


// DELIVERY SERVICES
$arDeliveryServices = [];
$shipmentCollection = $order->getShipmentCollection();
$shipment = $shipmentCollection->createItem();
$deliveryServicesAll = \Bitrix\Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment);
foreach( $deliveryServicesAll as $deliveryService ) {
	$arDeliveryServices[$deliveryService->getId()] =
		'[' . $deliveryService->getId() . ']' . $deliveryService->getName();
}


// CURRENCIES
$arCurrencies = \Bitrix\Currency\CurrencyManager::getCurrencyList();
$baseCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();


// PRICES
$arPrices = [];
$res = \Bitrix\Catalog\GroupTable::getList();
while( $arRes = $res->fetch() ) {
	$name = $arRes["NAME"];
	$rsGroup = \Bitrix\Catalog\GroupLangTable::getList(
		[
			'filter' => ['LANG' => LANGUAGE_ID, 'CATALOG_GROUP.ID' => $arRes["ID"]],
			'select' => ['NAME'],
		]
	);
	if( $arGroup = $rsGroup->fetch() ) {
		$name = $arGroup["NAME"];
	}
	$arPrices[$arRes["ID"]] = '[' . $arRes["ID"] . ']' . $name;
	if( $arRes["BASE"] == "Y" ) {
		$basePrice = $arRes["ID"];
	}
}


// ORDER PROPERTIES
$arProperties = [];
$propertyCollection = $order->getPropertyCollection()->getArray();
$arProperties = [];
$arForbidden = ["FILE", "DATE", "PRODUCT_CATEGORIES", "CONCRETE_PRODUCT", "LOCATION"];
foreach($propertyCollection["properties"] as $property) {
	if(!in_array($property['TYPE'], $arForbidden) && $property['PERSON_TYPE_ID'] == $arCurrentValues["PERSON_TYPE_ID"]){
		$arProperties[$property['CODE']] =
			'[' . $property['CODE'] . ']' . $property['NAME'];
	}
}


// REQUIRED PROPERTIES
$arPropertiesReq = [];
foreach( $arProperties as $code => $prop ) {
	if( in_array($code, $arCurrentValues["FORM_FIELDS"]) ) {
		$arPropertiesReq[$code] = $prop;
	}
}


// USER CONSENT
if ( $arCurrentValues["USE_USER_CONSENT"] == "BITRIX" ) {
	$arUserConsents = \Bitrix\Main\UserConsent\Agreement::getActiveList();
	ksort($arUserConsents);
}
if ( $arCurrentValues["USE_USER_CONSENT"] == "" ) {
	$arCurrentValues["USE_USER_CONSENT"] = "CUSTOM";
}



$arComponentParameters = [
	"GROUPS"     => [
		"ORDER" => ["NAME" => Loc::getMessage("IPL_SQO_GROUP_ORDER"), "SORT" => 250],
		"FORM"  => ["NAME" => Loc::getMessage("IPL_SQO_GROUP_FORM"), "SORT" => 260],
	],
	"PARAMETERS" => [

		"SITE_ID" => [
			"PARENT"  => "BASE",
			"NAME"    => Loc::getMessage("IPL_SQO_PARAMETER_SITE_ID"),
			"TYPE"    => "LIST",
			"VALUES"  => $arSites,
			"DEFAULT" => $arSiteDefault,
			"REFRESH" => "Y",
		],

		"IBLOCK_TYPE" => [
			"PARENT"  => "BASE",
			"NAME"    => Loc::getMessage("IPL_SQO_PARAMETER_IBLOCK_TYPE"),
			"TYPE"    => "LIST",
			"VALUES"  => $arIBlocksTypes,
			"DEFAULT" => "-",
			"REFRESH" => "Y",
		],
		"IBLOCK_ID"   => [
			"PARENT"            => "BASE",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_IBLOCK_ID"),
			"TYPE"              => "LIST",
			"VALUES"            => $arIBlocks,
			"DEFAULT"           => '-',
			"ADDITIONAL_VALUES" => "N",
			"REFRESH"           => "Y",
		],
		"ELEMENT_ID"  => [
			"PARENT"  => "BASE",
			"NAME"    => Loc::getMessage("IPL_SQO_PARAMETER_ELEMENT_ID"),
			"TYPE"    => "STRING",
			"DEFAULT" => '={$_REQUEST["ELEMENT_ID"]}',
		],

		"PERSON_TYPE_ID"      => [
			"PARENT"            => "ORDER",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_PERSON_TYPE_ID"),
			"TYPE"              => "LIST",
			"VALUES"            => $arPersonTypes,
			"DEFAULT"           => '',
			"ADDITIONAL_VALUES" => "N",
			"REFRESH"           => "Y",
		],
		"PAY_SYSTEM_ID"       => [
			"PARENT"            => "ORDER",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_PAY_SYSTEM_ID"),
			"TYPE"              => "LIST",
			"VALUES"            => $arPaySystems,
			"DEFAULT"           => '',
			"ADDITIONAL_VALUES" => "N",
		],
		"DELIVERY_SERVICE_ID" => [
			"PARENT"            => "ORDER",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_DELIVERY_SERVICE_ID"),
			"TYPE"              => "LIST",
			"VALUES"            => $arDeliveryServices,
			"DEFAULT"           => '',
			"ADDITIONAL_VALUES" => "N",
		],
		"CURRENCY_ID"         => [
			"PARENT"            => "ORDER",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_CURRENCY_ID"),
			"TYPE"              => "LIST",
			"VALUES"            => $arCurrencies,
			"DEFAULT"           => $baseCurrency,
			"ADDITIONAL_VALUES" => "N",
		],
		"PRICE_ID"            => [
			"PARENT"            => "ORDER",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_PRICE_ID"),
			"TYPE"              => "LIST",
			"VALUES"            => $arPrices,
			"DEFAULT"           => $basePrice,
			"ADDITIONAL_VALUES" => "N",
		],

		"FORM_FIELDS" => [
			"PARENT"            => "FORM",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_FORM_FIELDS"),
			"TYPE"              => "LIST",
			"VALUES"            => $arProperties,
			"DEFAULT"           => [],
			"ADDITIONAL_VALUES" => "N",
			"REFRESH"           => "Y",
			"MULTIPLE"          => "Y",
			"SIZE"              => 5,
		],

		"FORM_FIELDS_REQ" => [
			"PARENT"            => "FORM",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_FORM_FIELDS_REQ"),
			"TYPE"              => "LIST",
			"VALUES"            => $arPropertiesReq,
			"DEFAULT"           => [],
			"ADDITIONAL_VALUES" => "N",
			"REFRESH"           => "N",
			"MULTIPLE"          => "Y",
			"SIZE"              => 5,
		],

		'ADD_COMMENT' => [
			"PARENT"  => "FORM",
			"NAME"    => Loc::getMessage("IPL_SQO_PARAMETER_ADD_COMMENT"),
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => 'N',
			"REFRESH" => "N",
		],

		'COMMENT_REQUIRED' => [
			"PARENT"  => "FORM",
			"NAME"    => Loc::getMessage("IPL_SQO_PARAMETER_COMMENT_REQUIRED"),
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => 'N',
			"REFRESH" => "N",
		],

		"USE_USER_CONSENT" => [
			"PARENT"            => "FORM",
			"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_USE_USER_CONSENT"),
			"TYPE"              => "LIST",
			"VALUES"            => [
				"NO"     => Loc::getMessage("IPL_SQO_USE_USER_CONSENT_NO"),
				"CUSTOM" => Loc::getMessage("IPL_SQO_USE_USER_CONSENT_CUSTOM"),
				"BITRIX" => Loc::getMessage("IPL_SQO_USE_USER_CONSENT_BITRIX"),
			],
			"DEFAULT"           => "CUSTOM",
			"ADDITIONAL_VALUES" => "N",
			"REFRESH"           => "Y",
		],

		"CACHE_TIME" => [
			"DEFAULT" => 36000,
			"PARENT"  => "CACHE_SETTINGS",
		],
		"CACHE_TYPE" => [
			"PARENT"            => "CACHE_SETTINGS",
			"NAME"              => Loc::getMessage("COMP_PROP_CACHE_TYPE"),
			"TYPE"              => "LIST",
			"VALUES"            => [
				"A" => Loc::getMessage("COMP_PROP_CACHE_TYPE_AUTO") . " " . Loc::getMessage("COMP_PARAM_CACHE_MAN"),
				"Y" => Loc::getMessage("COMP_PROP_CACHE_TYPE_YES"),
				"N" => Loc::getMessage("COMP_PROP_CACHE_TYPE_NO"),
			],
			"DEFAULT"           => "N",
			"ADDITIONAL_VALUES" => "N",
			"REFRESH"           => "Y",
		],
	],
];

if ( $arCurrentValues["USE_USER_CONSENT"] == "CUSTOM" ) {
	$arComponentParameters["PARAMETERS"]["USER_CONSENT_TEXT"]  = [
		"PARENT"  => "FORM",
		"NAME"    => Loc::getMessage("IPL_SQO_PARAMETER_USER_CONSENT_TEXT"),
		"TYPE"    => "STRING",
		"DEFAULT" => '',
	];
}

if ( $arCurrentValues["USE_USER_CONSENT"] == "BITRIX" ) {
	$arComponentParameters["PARAMETERS"]["BITRIX_USER_CONSENTS"]  = [
		"PARENT"            => "FORM",
		"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_BITRIX_USER_CONSENTS"),
		"TYPE"              => "LIST",
		"VALUES"            => $arUserConsents,
		"DEFAULT"           => "1",
		"ADDITIONAL_VALUES" => "N",
		"REFRESH"           => "N",
	];
}

if ( $arCurrentValues["USE_USER_CONSENT"] != "NO" ) {
	$arComponentParameters["PARAMETERS"]["USER_CONSENT_STATE"]  = [
		"PARENT"            => "FORM",
		"NAME"              => Loc::getMessage("IPL_SQO_PARAMETER_USER_CONSENT_STATE"),
		"TYPE"              => "LIST",
		"VALUES"            => [
			"Y"     => Loc::getMessage("IPL_SQO_ON"),
			"N"     => Loc::getMessage("IPL_SQO_OFF"),
		],
		"DEFAULT"           => "Y",
		"ADDITIONAL_VALUES" => "N",
		"REFRESH"           => "N",
	];
}

