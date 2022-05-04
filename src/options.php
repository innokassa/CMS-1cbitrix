<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;
use Innokassa\MDK\Entities\Atoms\ReceiptItemType;
use Innokassa\MDK\Entities\Atoms\Vat;
use Innokassa\MDK\Settings\SettingsAbstract;

//require_once("include.php");

//##########################################################################

$request = HttpApplication::getInstance()->getContext()->getRequest();

$idModule = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
Loader::includeModule($idModule);
Loader::includeModule("sale");

//##########################################################################

// получить ассоциированный массив платежных систем [id => name, ...]
function GetPaySystems()
{
    $dbRes = \Bitrix\Sale\PaySystem\Manager::getList();

    $a = [-1 => "Все"];
    while ($aPaySystem = $dbRes->fetch()) {
        $a[$aPaySystem["PAY_SYSTEM_ID"]] = $aPaySystem["NAME"];
    }

    return $a;
}

//**************************************************************************

// получить массив сайтов [lid => name, ...]
function GetSites()
{
    $dbRes = SiteTable::getList();
    $aSites = [];
    while ($aSite = $dbRes->fetch()) {
        $aSites[$aSite["LID"]] = $aSite["NAME"];
    }

    return $aSites;
}

//**************************************************************************

// рендер настроек
function RenderSettings($idModule, $aSettings, $sSiteId)
{
    $a = [];
    foreach ($aSettings as $value) {
        $name = $value[0];
        $name2 = $value[0] . "_" . $sSiteId;
        $desc = $value[1];
        $default = $value[2];
        $type = $value[3][0];
        $val = $value[3][1];

        $realval = Option::get($idModule, $name, $default, $sSiteId);
        $realval = ($realval !== null ? $realval : $default);

        $inner = "";

        switch ($type) {
            case "text":
                $inner = sprintf(
                    '<input type="text" size="%s" maxlength="255" value="%s" name="%s">',
                    $val,
                    $realval,
                    $name2
                );
                break;
            case "selectbox":
                $aOptions = [];
                foreach ($val as $key2 => $val2) {
                    $aOptions[] = sprintf(
                        '<option value="%s" %s>%s</option>',
                        $key2,
                        ($realval == $key2 ? 'selected=""' : ''),
                        $val2
                    );
                }
                $inner = sprintf(
                    '<select name="%s">%s</select>',
                    $name2,
                    implode("\n", $aOptions)
                );
                break;
            case "checkbox":
                $inner = sprintf(
                    '<input type="checkbox" id="%s" name="%s" value="%s" %s class="adm-designed-checkbox"><label class="adm-designed-checkbox-label" for="%s"  title=""></label>',
                    $name2,
                    $name2,
                    $realval,
                    ($realval == "Y" ? 'checked=""' : ''),
                    $name2
                );
                break;
            default:
                break;
        }

        $line = sprintf(
            '<div style="display: block; margin-bottom: 5px;"><label style="display: inline-block;width:50%%;text-align: right;">%s</label>%s</div>',
            $desc,
            $inner
        );

        $a[] = $line;
    }

    return implode("\n", $a);
}

//**************************************************************************

//! получить ассоциированный массив статусов заказов [id => name, ...]
function GetStatusesOrder()
{
    $query = Bitrix\Sale\Internals\StatusTable::query();
    $query->setSelect([
        'ID', 'SORT', 'TYPE', 'NAME' => 'STATUS_LANG.NAME'
    ]);
    $query->where(
        \Bitrix\Main\ORM\Query\Query::filter()
            ->logic("AND")
            ->where('TYPE', '=', "O")
    );
    $query->where(
        \Bitrix\Main\ORM\Query\Query::filter()
            ->logic('OR')
            ->where('STATUS_LANG.LID', '=', LANGUAGE_ID)
            ->where('STATUS_LANG.LID', null)
    );
    $query->setOrder(["SORT" => "ASC"]);

    $a = [-1 => "Не выбрано"];
    $aRes = $query->exec()->fetchAll();
    foreach ($aRes as $value) {
        $a[$value["ID"]] = $value["NAME"];
    }

    return $a;
}

//##########################################################################

$aOrderStatses = GetStatusesOrder();
$aPaySystems = GetPaySystems();
$aSiteIds = GetSites();
$schemes = [
    SettingsAbstract::SCHEME_PRE_FULL => 'Предоплата, полный расчет',
    SettingsAbstract::SCHEME_ONLY_FULL => 'Полный расчет'
];

$vats = [];
foreach (Vat::all() as $vat) {
    $vats[$vat->getCode()] = $vat->getName();
}

$itemTypes = [];
foreach (ReceiptItemType::all() as $temType) {
    $itemTypes[$temType->getCode()] = $temType->getName();
}


//##########################################################################

// массив настроек
$aTabs = [
    [
        "DIV"    => "edit-acc-fermarunet",
        "TAB"    => "Настройки кассы FERMA.ru.net",
        "OPTIONS" => [
            ["cashbox", "ID группы касс:", "", ["text", 20]],
            ["actor_id", "Актор id:", "", ["text", 20]],
            ["actor_token", "Токен актора:", "", ["text", 20]],
            [
                "taxation",
                "Налогообложение:",
                "0",
                [
                    "selectbox",
                    [
                        0 => "Не выбрано",
                        1 => "ОРН",
                        2 => "УСН доход",
                        4 => "УСН доход - расход",
                        16 => "ЕСН",
                        32 => "ПСН"
                    ]
                ]
            ],
            [
                "location",
                "Адрес сайта:",
                '',
                ["text", 32]
            ],
            [
                "type_default_items",
                "Тип позиции чека по умолчанию:",
                array_keys($itemTypes)[0],
                [
                    "selectbox",
                    $itemTypes
                ]
            ],
            [
                "vat_default_items",
                "НДС позиции чека по умолчанию:",
                array_keys($vats)[0],
                [
                    "selectbox",
                    $vats
                ]
            ],
            [
                "vat_default_items",
                "НДС доставки:",
                array_keys($vats)[0],
                [
                    "selectbox",
                    $vats
                ]
            ],
            [
                "paysystem",
                "Создавать чеки для оплаты:",
                array_keys($aPaySystems)[0],
                [
                    "selectbox",
                    $aPaySystems
                ]
            ],
            [
                "scheme",
                "Схема фискализации:",
                array_keys($schemes)[0],
                [
                    "selectbox",
                    $schemes
                ]
            ],
            [
                "order_status_receipt_pre",
                "Статус заказа для чека предоплаты:",
                array_keys($aOrderStatses)[0],
                [
                    "selectbox",
                    $aOrderStatses
                ]
            ],
            [
                "order_status_receipt_full",
                "Статус заказа для чека полного расчета:",
                array_keys($aOrderStatses)[0],
                [
                    "selectbox",
                    $aOrderStatses
                ]
            ],
            [
                "only_internal",
                "Обрабатывать заказы только из интернет-магазина:",
                "N",
                ["checkbox"]
            ]
        ]
    ]
];

//##########################################################################
// проверка настроек

$aSettingsError = [];
foreach ($aSiteIds as $id => $name) {
    $aSettingsError[$id] = [];
}

//если сохранение настроек
if ($request->isPost() && check_bitrix_sessid()) {
    //exit_print_r($request);
    $aOptions = [];
    foreach ($aSiteIds as $sSiteId => $name) {
        $aOptions[$sSiteId] = [];
        foreach ($aTabs as $aTab) {
            foreach ($aTab["OPTIONS"] as $aOption) {
                if (!is_array($aOption)) {
                    continue;
                }

                $type = $aOption[3][0];

                if ($request["apply"]) {
                    if ($type == "checkbox") {
                        $aOptions[$sSiteId][$aOption[0]] = $request->getPost($aOption[0] . '_' . $sSiteId) !== null ? "Y" : "N";
                    } else {
                        $aOptions[$sSiteId][$aOption[0]] = trim($request->getPost($aOption[0] . '_' . $sSiteId));
                    }
                } elseif ($request["default"]) {
                    $aOptions[$sSiteId][$aOption[0]] = trim($aOption[2]);
                }
            }
        }
    }

    //exit_print_r($request, $aOptions);

    $existsError = false;

    foreach ($aSiteIds as $sSiteId => $name) {
        //проверка настроек

        try {
            $transfer = new Innokassa\MDK\Net\Transfer(
                new Innokassa\MDK\Net\NetClientCurl(),
                new Innokassa\MDK\Net\ConverterApi(),
                new Innokassa\MDK\Logger\LoggerFile()
            );
            $conn = new Innokassa\MDK\Services\ConnectorBase($transfer);
            $conn->testSettings(new SettingsRaw($aOptions), $sSiteId);
        } catch (Innokassa\MDK\Exceptions\SettingsException $e) {
            $aSettingsError[$sSiteId][] = $e->getMessage();
            $existsError = true;
        }
    }

    if (!$existsError) {
        foreach ($aOptions as $sSiteId => $aSettings) {
            foreach ($aSettings as $sKey => $sValue) {
                Option::set($idModule, $sKey, $sValue, $sSiteId);
            }
        }

        LocalRedirect(
            sprintf(
                '%s?mid=%s&lang=%s',
                $APPLICATION->GetCurPage(),
                $idModule,
                LANG
            )
        );
    }
}

//##########################################################################
// отрисовка формы с настройками

foreach ($aSiteIds as $sSiteId => $sSiteName) {
    $aSubTabs[] = [
        "DIV" => "opt_site_$sSiteId",
        "TAB" => "$sSiteName ($sSiteId)",
        'TITLE' => '',
        "OPTIONS" => $aTabs["OPTIONS"]
    ];
}
$subTabControl = new CAdminViewTabControl("subTabControl", $aSubTabs);

$oTabControl = new CAdminTabControl("tabControl", $aTabs);
$oTabControl->Begin();

?><form action="<?php echo($APPLICATION->GetCurPage()); ?>?mid=<?php echo($idModule); ?>&lang=<?php echo(LANG); ?>" method="post">
<?php

$oTabControl->BeginNextTab();
foreach ($aSiteIds as $sSiteId => $sSiteName) {
    if (count($aSettingsError[$sSiteId]) > 0) {
        echo "<div style='width: 100%; margin: 5px; padding: 5px; text-align: center; color: red; font-size: 18px;'>$sSiteId: " . implode("<br/>", $aSettingsError[$sSiteId]) . "</div><br/><br/>";
    }
}

$subTabControl->Begin();
foreach ($aSiteIds as $sSiteId => $sSiteName) {
    $subTabControl->BeginNextTab();

    foreach ($aTabs as $aTab) {
        if ($aTab["OPTIONS"]) {
            echo RenderSettings($idModule, $aTab["OPTIONS"], $sSiteId);
        }
    }
}
//__AdmSettingsDrawList($idModule, $aTab["OPTIONS"]);
$subTabControl->End();
$oTabControl->Buttons();

?><input type="submit" name="apply" value="Применить" class="adm-btn-save" />
<?php echo(bitrix_sessid_post()); ?>
</form>
<?php $oTabControl->End();
