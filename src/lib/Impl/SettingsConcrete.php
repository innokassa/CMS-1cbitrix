<?php

namespace Innokassa\Fiscal\Impl;

use Innokassa\MDK\Settings\SettingsAbstract;
use Innokassa\MDK\Exceptions\SettingsException;

class SettingsConcrete extends SettingsAbstract
{
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getActorId(string $siteId = ''): string
    {
        return $this->get('actor_id', $siteId);
    }

    public function getActorToken(string $siteId = ''): string
    {
        return $this->get('actor_token', $siteId);
    }

    public function getCashbox(string $siteId = ''): string
    {
        return $this->get('cashbox', $siteId);
    }

    public function getLocation(string $siteId = ''): string
    {
        return $this->get('location', $siteId);
    }

    public function getTaxation(string $siteId = ''): int
    {
        return intval($this->get('taxation', $siteId));
    }

    public function getScheme(string $siteId = ''): int
    {
        return intval($this->get('scheme', $siteId));
    }

    public function getVatShipping(string $siteId = ''): int
    {
        return intval($this->get('vat_shipping', $siteId));
    }

    public function getVatDefaultItems(string $siteId = ''): int
    {
        return intval($this->get('vat_default_items', $siteId));
    }

    public function getTypeDefaultItems(string $siteId = ''): int
    {
        return intval($this->get('type_default_items', $siteId));
    }

    public function getOrderStatusReceiptPre(string $siteId = ''): string
    {
        return $this->get('order_status_receipt_pre', $siteId);
    }

    public function getOrderStatusReceiptFull(string $siteId = ''): string
    {
        return $this->get('order_status_receipt_full', $siteId);
    }

    //######################################################################

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
