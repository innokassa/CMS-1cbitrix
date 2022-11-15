<?php

namespace Innokassa\Fiscal\Impl;

use Bitrix\Main\SiteTable;
use Bitrix\Main\Config\Option;
use Innokassa\MDK\Client;
use Innokassa\MDK\Net\Transfer;
use Innokassa\MDK\Net\ConverterApi;
use Innokassa\MDK\Net\NetClientCurl;
use Innokassa\MDK\Services\PipelineBase;
use Innokassa\MDK\Services\AutomaticBase;
use Innokassa\MDK\Services\ConnectorBase;
use Innokassa\MDK\Storage\ConverterStorage;

/**
 * Фабрика клиента MDK
 */
class ClientFactory
{
    public static function build(): Client
    {
        $dbRes = SiteTable::getList();
        $options = [];
        while ($site = $dbRes->fetch()) {
            $options[$site['LID']] = Option::getForModule('innokassa.fiscal', $site['LID']);
        }

        $receiptIdFactory = new ReceiptIdFactoryMetaConcrete();

        $settings = new SettingsConcrete($options);
        $receiptStorage = new ReceiptStorageConcrete(
            $GLOBALS['DB'],
            new ConverterStorage($receiptIdFactory)
        );
        $receiptAdapter = new ReceiptAdapterConcrete($settings);
        $transfer = new Transfer(
            new NetClientCurl(),
            new ConverterApi()
        );

        $automatic = new AutomaticBase(
            $settings,
            $receiptStorage,
            $transfer,
            $receiptAdapter,
            $receiptIdFactory
        );
        $pipeline = new PipelineBase($settings, $receiptStorage, $transfer, $receiptIdFactory);
        $connector = new ConnectorBase($transfer);

        $client = new Client(
            $settings,
            $receiptStorage,
            $automatic,
            $pipeline,
            $connector
        );

        return $client;
    }
}
