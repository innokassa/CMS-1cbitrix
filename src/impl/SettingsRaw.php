<?php

use Innokassa\MDK\Exceptions\SettingsException;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class SettingsRaw extends SettingsPreAbstract
{
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get(string $name, string $siteId = '')
    {
        if ($siteId) {
            if (isset($this->settings[$siteId][$name])) {
                return $this->settings[$siteId][$name];
            }
        } else {
            if (isset($this->settings[$name])) {
                return $this->settings[$name];
            }
        }

        throw new SettingsException("Настройка '$name' не инициализирована");
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    private $settings = [];
}
