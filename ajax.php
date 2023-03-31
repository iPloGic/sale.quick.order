<?
/** @global \CMain $APPLICATION */
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

$parameters = $request->get('parameters');
$parameters["IS_AJAX"] = "Y";

$APPLICATION->IncludeComponent(
	'iplogic:sale.quick.order',
	"",
	$parameters,
	false
);