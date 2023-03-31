# README #

Компонент **iplogic:sale.quick.order** представляет собой форму быстрого заказа (покупки в один клик) товара каталога. Заказ создается в общем списке заказов модуля интернет-магазина.

### Подключение ###

Создайте на сайте директорию `\local\components\iplogic`. Скопируйте в нее скачанную директорию компонента iplogic:sale.quick.order.

В коде модульного окна, либо непосредственно в коде компонента карточки товара или списка вставьте код:

```
<?$APPLICATION->IncludeComponent(
	"iplogic:sale.quick.order",
	"",
	array(
		"COMPONENT_TEMPLATE" => "",
		"SITE_ID" => "s1",
		"IBLOCK_TYPE" => "catalog",
		"IBLOCK_ID" => "1",
		"ELEMENT_ID" => $ID,
		"PERSON_TYPE_ID" => "1",
		"PAY_SYSTEM_ID" => "1",
		"DELIVERY_SERVICE_ID" => "3",
		"CURRENCY_ID" => "RUB",
		"PRICE_ID" => "1",
		"FORM_FIELDS" => array(
			0 => "FIO",
			1 => "EMAIL",
			2 => "PHONE",
		),
		"FORM_FIELDS_REQ" => array(
			0 => "FIO",
			1 => "PHONE",
		),
		"ADD_COMMENT" => "N",
		"COMMENT_REQUIRED" => "N",
		"USE_USER_CONSENT" => "BITRIX",
		"BITRIX_USER_CONSENTS" => 1,
		"USER_CONSENT_STATE" => "Y",
		"CACHE_TYPE" => "N",
		"CACHE_TIME" => "36000"
	),
	false
);?>
```

### Параметры ###

Параметры компонента описаны в таблице. Стандартные параметры для компонента опущены, о них можно узнать в документации Битрикс.

| Параметр | Описание                    |
| ------------- | ------------------------------ |
| SITE_ID      | Идентификатор сайта.  |
| IBLOCK_TYPE      | Тип инфоблока каталога товаров.  |
| IBLOCK_ID      | Идентификатор инфоблока каталога товаров.  |
| ELEMENT_ID      | Идентификатор заказываемого товара.  |
| PERSON_TYPE_ID      | Тип плательщика для заказа.  |
| PAY_SYSTEM_ID      | ID платежной системы заказа.  |
| DELIVERY_SERVICE_ID      | ID службы доставки заказа.  |
| CURRENCY_ID      | Код валюты заказа.  |
| PRICE_ID      | ID типа цены заказа.  |
| FORM_FIELDS      | Список кодов полей для заполнения. Берется из настроек магазина.  |
| FORM_FIELDS_REQ      | Список обязательных полей.  |
| ADD_COMMENT      | Добавить поле комментария пользователя.  |
| COMMENT_REQUIRED      | Комментарий пользователя обезательное поле (Y/N).  |
| USE_USER_CONSENT      | Используемое согласие пользователя. CUSTOM - своё произвольное согласие. BITRIX - согласие пользователя из настроек Битрикса. NO - не выводить.  |
| BITRIX_USER_CONSENTS      | ID согласия пользователя из настроек Битрикса (для значения USE_USER_CONSENT BITRIX).  |
| USER_CONSENT_TEXT      | Текст согласия (для значения USE_USER_CONSENT CUSTOM).  |
| USER_CONSENT_STATE      | Устанавливать ли галку в чекбоксе согласия изначально (Y/N).  |


