<?php

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockLangTable;

class Personal extends \CSaleActionCtrlBasketGroup
{
    public static function GetClassName()
    {
        return __CLASS__;
    }

    public static function GetControlID()
    {
        return "DiscountPersonal";
    }

    public static function GetControlDescr()
    {
        return parent::GetControlDescr();
    }

    public static function GetAtoms()
    {
        return static::GetAtomsEx(false, false);
    }

    public static function GetControlShow($arParams)
    {
        $arAtoms = static::GetAtomsEx(false, false);
        $arResult = [
            "controlId" => static::GetControlID(),
            "group" => false,
            "label" => "Применить скидку из справочника",
            "defaultText" => "",
            "showIn" => static::GetShowIn($arParams["SHOW_IN_GROUPS"]),
            "control" => [
                "Применить персональную скидку из справочника",
                $arAtoms["HLB"]
            ]
        ];

        return $arResult;
    }

    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = (true === $boolEx ? true : false);
        $hlbList = [];
        if (Loader::includeModule('highloadblock')) {
            $dbRes = HighloadBlockTable::GetList([]);
            while ($el = $dbRes->fetch()) {
                $hlbList[$el['ID']] = $el['NAME'];
            }
            $res = HighloadBlockLangTable::GetList(['filter' => ['=LID' => LANGUAGE_ID]]);
            while ($el = $res->fetch()) {
                if ($hlbList[$el['ID']]) {
                    $hlbList[$el['ID']] = $el['NAME'] . " [" . $hlbList[$el['ID']] . "]";
                }
            }
        }
        $arAtomList = [
            "HLB" => [
                "JS" => [
                    "id" => "HLB",
                    "name" => "extra",
                    "type" => "select",
                    "values" => $hlbList,
                    "defaultText" => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "ATOM" => [
                    "ID" => "HLB",
                    "FIELD_TYPE" => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE" => "N",
                    "VALIDATE" => "list"
                ]
            ],
        ];
        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom) {
                $arOneAtom = $arOneAtom["JS"];
            }
            if (isset($arOneAtom)) {
                unset($arOneAtom);
            }
        }
        return $arAtomList;
    }

    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        return __CLASS__ . "::applyProductDiscount(" . $arParams["ORDER"] . ", " . "\"" . $arOneCondition["HLB"] . "\"" . ");";
    }

    /**
     * Применяет персональную скидку к товарам корзины
     * @param $arOrder
     * @throws \Bitrix\Main\LoaderException
     */
    public static function applyProductDiscount(&$arOrder, $hlb)
    {

        $userId = $arOrder['USER_ID'];
        Loader::includeModule('highloadblock');
        if (!$userId) {
            return;
        };
        $hlblock = HighloadBlockTable::getById($hlb)->fetch();
        if (!$hlblock) {
            return;
        }
        $entity = HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        $user = \CUser::getById($userId)->fetch();
        $userXmlId = $user["XML_ID"];

        $bonusInfo = $entityClass::getList([
            'filter' => [
                [
                    'LOGIC' => "OR",
                    '=UF_USER_ID' => $userId,
                    '=UF_USER_XML_ID' => $userXmlId
                ]
            ],
        ])->fetch();

        $data = json_decode($bonusInfo["UF_DATA"], true);

        if ($data) {
            $percent = (float)$data["discountPercentage"];
            if ($percent <= 0) {
                return;
            }
            
            foreach ($arOrder['BASKET_ITEMS'] as &$product) {
                $product['DISCOUNT_PRICE'] = $product['BASE_PRICE'] * $percent / 100;
                $product['PRICE'] = $product['BASE_PRICE'] - $product['DISCOUNT_PRICE'];
            }
        }

        unset($product);
    }
}
