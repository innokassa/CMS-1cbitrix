<?php

namespace Innokassa\Fiscal\Impl;

use Bitrix\Main\SiteTable;
use Bitrix\Main\Config\Option;
use Innokassa\MDK\Client;
use Innokassa\MDK\Net\Transfer;
use Innokassa\MDK\Net\ConverterApi;
use Innokassa\MDK\Logger\LoggerFile;
use Innokassa\MDK\Net\NetClientCurl;
use Innokassa\MDK\Services\PipelineBase;
use Innokassa\MDK\Services\AutomaticBase;
use Innokassa\MDK\Services\ConnectorBase;
use Innokassa\MDK\Storage\ConverterStorage;
use Innokassa\MDK\Entities\ReceiptId\ReceiptIdFactoryMeta;

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

        $settings = new SettingsConcrete($options);
        $receiptStorage = new ReceiptStorageConcrete(
            $GLOBALS['DB'],
            new ConverterStorage(new ReceiptIdFactoryMeta())
        );
        $receiptAdapter = new ReceiptAdapterConcrete($settings);
        $logger = new LoggerFile();
        $transfer = new Transfer(
            new NetClientCurl(),
            new ConverterApi(),
            $logger
        );

        $automatic = new AutomaticBase(
            $settings,
            $receiptStorage,
            $transfer,
            $receiptAdapter,
            new ReceiptIdFactoryMeta()
        );
        $pipeline = new PipelineBase($settings, $receiptStorage, $transfer);
        $connector = new ConnectorBase($transfer);

        $client = new Client(
            $settings,
            $receiptStorage,
            $automatic,
            $pipeline,
            $connector,
            $logger
        );

        return $client;
    }
}
