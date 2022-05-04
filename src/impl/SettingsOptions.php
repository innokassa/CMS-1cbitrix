<?php

use Bitrix\Main\Config\Option;
use Innokassa\MDK\Exceptions\SettingsException;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class SettingsOptions extends SettingsPreAbstract
{
    public function get(string $name, string $siteId = '')
    {
        if ($value = Option::get('innokassa.fiscal', $name, null, $siteId)) {
            return $value;
        }

        throw new SettingsException("Настройка '$name' не инициализирована");
    }
}
