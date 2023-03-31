<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//echo "<pre>"; print_r($arResult); echo "</pre>";

use \Bitrix\Main\Localization\Loc;
?>
<div class="sqo-underlayer" id="sqo-underlayer"></div>
<div class="sqo-popup" id="sqo-popup">
	<div class="sqo-popup-intro">
		<div class="pop-up-title"><?=Loc::getMessage('IPL_SQO_FORM_HEADER_CAPTION')?></div>
	</div>
	<a class="sqo-close"><i class="fa fa-times" aria-hidden="true"></i></a>
	<div class="sqo-form">
		<div class="sqo-result" id="ipl_quick_order_result">
			<div class="sqo-result_success"><?=Loc::getMessage('IPL_SQO_ORDER_SUCCESS')?></div>
			<div class="sqo-result_text"><?=Loc::getMessage('IPL_SQO_ORDER_SUCCESS_TEXT')?></div>
			<div class="sqo-result_fail"><?=Loc::getMessage('IPL_SQO_ORDER_ERROR')?></div>
			<div class="sqo-result_error_text"></div>
		</div>
		<form method="post" id="sqo-form" action="">
			<input type="hidden" name="PRODUCT_ID" id="sqo_id_PRODUCT_ID" value="<?=$arParams["ELEMENT_ID"]?>">
			<?foreach($arResult['FIELDS'] as $field):?>
					<? if ($field["TYPE"] != "Y/N") {?>
					<div class="sqo-form-control">
						<label class="description">
							<?if ($field["REQUIRED"] == "Y"):?><span class="star">*</span> <?endif;?>
							<?=$field["NAME"]?>
						</label>
						<?if ($field["TYPE"] == "STRING" || $field["TYPE"] == "NUMBER" || $field["TYPE"] == "DATE") {
							$type = "text";
							if ($field["IS_PHONE"] == "Y") {
								$type = "tel";
							}
							if ($field["IS_EMAIL"] == "Y") {
								$type = "email";
							} ?>
							<input
									type="<?=$type?>"
									name="<?=$field["CODE"]?>"
									value="<?=$field["DEFAULT_VALUE"]?>"
									id="sqo_id_<?=$field["CODE"]?>"
							>
						<? } ?>
						<?if ($field["TYPE"] == "ENUM") { ?>
							<select name="<?=$field["CODE"]?>" id="sqo_id_<?=$field["CODE"]?>">
								<?foreach($field["OPTIONS"] as $value => $name) { ?>
									<option
											value="<?=$value?>"
											<?=($field["DEFAULT_VALUE"] == $value ? " selected" : "")?>
									>
										<?=$name?>
									</option>
								<? } ?>
							</select>
						<? } ?>
					<? } else { ?>
						<div class="sqo-form-control sqo-checkbox">
						<input
								type="checkbox"
								name="<?=$field["CODE"]?>"
								value="Y"
								id="sqo_id_<?=$field["CODE"]?>"
								<?=($field["DEFAULT_VALUE"] == "Y" ? " checked" : "")?>
						>
						<label class="checkbox" for="sqo_id_<?=$field["CODE"]?>">
							<?if ($field["REQUIRED"] == "Y"):?><span class="star">*</span> <?endif;?>
							<?=$field["NAME"]?>
						</label>
					<? } ?>
				</div>
			<?endforeach;?>
			<?if($arParams["ADD_COMMENT"]=="Y"):?>
				<div class="sqo-form-control">
					<label class="description">
						<?if ($arParams["COMMENT_REQUIRED"] == "Y"):?><span class="star">*</span> <?endif;?>
						<?=Loc::getMessage('IPL_SQO_COMMENT')?>
					</label>
					<textarea name="USER_COMMENT" id="sqo_id_USER_COMMENT"></textarea>
				</div>
			<?endif;?>
			<?if($arParams["USE_USER_CONSENT"]!="NO"):?>
				<div class="sqo-form-control sqo-checkbox">
					<input
							type="checkbox"
							name="USER_CONSENT"
							value="1"
							id="sqo_id_USER_CONSENT"
							<?=($arParams["USER_CONSENT_STATE"]=="Y" ? " checked" : "")?>
					>
					<label class="checkbox" for="sqo_id_USER_CONSENT">
						<span class="star">*</span> <?=htmlspecialchars_decode($arResult["CONSENT_TEXT"])?>
					</label>
				</div>
			<?endif;?>
			<?=bitrix_sessid_post()?>
		</form>
		<div class="clearfix">
			<!--noindex-->
			<button
					class="button sqo-form_button"
					id="sqo-form_button"
					name="sqo-form_button"
					value="<?=Loc::getMessage('IPL_SQO_ORDER_BUTTON_CAPTION')?>"
			>
				<?=Loc::getMessage("IPL_SQO_ORDER_BUTTON_CAPTION")?>
			</button>
			<!--/noindex-->
		</div>
	</div>
</div>
<?
foreach($arParams as $key => $val) {
	if(substr($key,0,1) == "~")
		continue;
	if($key == "ACTION")
		continue;
	$arParameters[$key] = $val;
}
$jsFields = [];
foreach($arResult['FIELDS'] as $field) {
	$jsFields[] = [
		"code" => $field["CODE"],
		"required" => $field["REQUIRED"],
		"type" => $field["TYPE"],
		"is_phone" => $field["IS_PHONE"],
		"is_email" => $field["IS_EMAIL"],
		"is_location" => $field["IS_LOCATION"],
	];
}
$arJsParams = [
	"parameters" => $arParameters,
	"componentPath" => $componentPath,
	"fields" => $jsFields,
];
?>
<script>
	window.obJSSaleQuickOrderComponent = new JSSaleQuickOrderComponent(<?=CUtil::PhpToJSObject($arJsParams)?>);
</script>