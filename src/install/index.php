<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class innokassa_fiscal extends CModule
{
    public $MODULE_ID = 'innokassa.fiscal';
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    //########################################################################

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_NAME = 'Innokassa.fiscal';
        $this->MODULE_DESCRIPTION = 'Сервис для фискализации интернет-продаж.';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->PARTNER_NAME = 'INNOKASSA.ru';
        $this->PARTNER_URI = 'https://innokassa.ru/';
    }

    //########################################################################

    public function doInstall()
    {
        global $APPLICATION;

        if (!in_array('curl', get_loaded_extensions())) {
            $APPLICATION->ThrowException('Для работы модуля необходимо наличие curl');
            return false;
        }

        $this->installDB();
        $this->installEvents();
        $this->installAgents();
        $this->installFiles();

        RegisterModule($this->MODULE_ID);

        LocalRedirect(
            sprintf('/bitrix/admin/settings.php?lang=ru&mid=%s&mid_menu=1', $this->MODULE_ID)
        );
    }

    //************************************************************************

    public function installFiles()
    {
    }

    //************************************************************************

    public function installEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandler(
            'main',
            'OnAdminContextMenuShow',
            $this->MODULE_ID,
            '\Innokassa\Fiscal\Events',
            'onAdminContextMenuShow'
        );

        $eventManager->registerEventHandler(
            'sale',
            'OnSaleOrderBeforeSaved',
            $this->MODULE_ID,
            '\Innokassa\Fiscal\Events',
            'onSaleOrderBeforeSaved'
        );
    }

    //************************************************************************

    public function installAgents()
    {
        CAgent::AddAgent("\Innokassa\Fiscal\AgentFiscal::pipeline();", $this->MODULE_ID, "N", 600, "", "Y");
    }

    //************************************************************************

    public function installDB()
    {
        global $DB;
        $table = self::$table;

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `subtype` TINYINT,
            `cashbox` VARCHAR(255) NOT NULL,
            `order_id` VARCHAR(255) NOT NULL,
            `site_id` VARCHAR(255) NOT NULL,
            `receipt_id` VARCHAR(64) NOT NULL,
            `status` TINYINT NOT NULL,
            `type` TINYINT NOT NULL,
            `items` TEXT NOT NULL,
            `taxation` TINYINT NOT NULL,
            `amount` TEXT NOT NULL,
            `customer` TEXT NOT NULL,
            `notify` TEXT NOT NULL,
            `location` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `filter` (`order_id`, `type`, `subtype`, `status`)
        ) ENGINE = InnoDB;";

        $DB->Query(
            $sql,
            false,
            sprintf("Module %s, DB error(%s:%s)", $this->MODULE_ID, __FILE__, __LINE__)
        );
    }

    //########################################################################

    public function doUninstall()
    {
        $this->uninstallEvents();
        $this->uninstallAgents();
        $this->uninstallDB();
        $this->uninstallFiles();

        UnRegisterModule($this->MODULE_ID);
    }

    //************************************************************************

    public function uninstallFiles()
    {
    }

    //************************************************************************

    public function uninstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAdminContextMenuShow',
            $this->MODULE_ID,
            '\Innokassa\Fiscal\Events',
            'onAdminContextMenuShow'
        );

        $eventManager->unRegisterEventHandler(
            'sale',
            'OnSaleOrderBeforeSaved',
            $this->MODULE_ID,
            '\Innokassa\Fiscal\Events',
            'onSaleOrderBeforeSaved'
        );
    }

    //************************************************************************

    public function uninstallAgents()
    {
        CAgent::RemoveAgent('AgentFiscal();', $this->MODULE_ID);
    }

    //************************************************************************

    public function uninstallDB()
    {
        global $DB;
        $sql = sprintf('DROP TABLE `%s`', self::$table);
        $DB->Query(
            $sql,
            false,
            sprintf('Module %s, DB error(%s:%s)', $this->MODULE_ID, __FILE__, __LINE__)
        );
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /** @var string */
    private static $table = 'innokassa_fiscal';
}
