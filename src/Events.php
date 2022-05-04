<?php

use Innokassa\MDK\Settings\SettingsAbstract;
use Innokassa\MDK\Entities\Atoms\ReceiptSubType;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class Events
{
    public static function onSaleOrderBeforeSaved(Bitrix\Main\Event $oEvent)
    {
        $mdk = ClientFactory::build();
        $settings = $mdk->componentSettings();
        $automatic = $mdk->serviceAutomatic();

        $order = $oEvent->getParameter("ENTITY");
        $orderFields = $order->getFieldValues();
        $siteId = $order->getSiteId();

        if (
            !(
                !$order->isExternal()
                || $settings->get("only_internal", $siteId) != "Y"
            )
        ) {
            return;
        }

        // проверяем все ли системы оплаты соответсвуют тому что выбрано в модуле
        foreach ($order->getPaymentCollection() as $payment) {
            $paymentFields = $payment->getFieldValues();

            $isCorrectPaySystem = (
                $settings->get("paysystem", $siteId) == -1
                || $settings->get("paysystem", $siteId) == $paymentFields["PAY_SYSTEM_ID"]
            );

            if ($paymentFields["PAID"] == "Y" && !$isCorrectPaySystem) {
                return;
            }
        }

        try {
            if (
                $settings->getScheme($siteId) == SettingsAbstract::SCHEME_PRE_FULL
                && $settings->getOrderStatusReceiptPre($siteId) == $orderFields["STATUS_ID"]
            ) {
                $automatic->fiscalize($order->getId(), $siteId, ReceiptSubType::PRE);
            } elseif ($settings->getOrderStatusReceiptFull($siteId) == $orderFields["STATUS_ID"]) {
                $automatic->fiscalize($order->getId(), $siteId, ReceiptSubType::FULL);
            }
        } catch (Exception $e) {
        }
    }

    //########################################################################

    public static function onAdminContextMenuShow(&$aItems)
    {
        if (
            $GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/sale_order_view.php'
            && isset($_GET["ID"])
        ) {
            $aItems[] = [
                "TEXT" => 'Управление чеками',
                "LINK" => 'https://crm.innokassa.ru/',
                'LINK_PARAM' => 'target=_blank',
                "ICON" => "btn_green"
            ];
        }
    }
}
