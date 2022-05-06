<?php

namespace Innokassa\Fiscal\Impl;

use Innokassa\MDK\Settings\SettingsAbstract;
use Innokassa\MDK\Exceptions\SettingsException;

/**
 * Реализация настроек из массива данных
 */
class SettingsConcrete extends SettingsAbstract
{
    /**
     * @param array<string, array<string, mixed>> $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public function getActorId(string $siteId = ''): string
    {
        return $this->get('actor_id', $siteId);
    }

    /**
     * @inheritDoc
     */
    public function getActorToken(string $siteId = ''): string
    {
        return $this->get('actor_token', $siteId);
    }

    /**
     * @inheritDoc
     */
    public function getCashbox(string $siteId = ''): string
    {
        return $this->get('cashbox', $siteId);
    }

    /**
     * @inheritDoc
     */
    public function getLocation(string $siteId = ''): string
    {
        return $this->get('location', $siteId);
    }

    /**
     * @inheritDoc
     */
    public function getTaxation(string $siteId = ''): int
    {
        return intval($this->get('taxation', $siteId));
    }

    /**
     * @inheritDoc
     */
    public function getScheme(string $siteId = ''): int
    {
        return intval($this->get('scheme', $siteId));
    }

    /**
     * @inheritDoc
     */
    public function getVatShipping(string $siteId = ''): int
    {
        return intval($this->get('vat_shipping', $siteId));
    }

    /**
     * @inheritDoc
     */
    public function getVatDefaultItems(string $siteId = ''): int
    {
        return intval($this->get('vat_default_items', $siteId));
    }

    /**
     * @inheritDoc
     */
    public function getTypeDefaultItems(string $siteId = ''): int
    {
        return intval($this->get('type_default_items', $siteId));
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusReceiptPre(string $siteId = ''): string
    {
        return $this->get('order_status_receipt_pre', $siteId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusReceiptFull(string $siteId = ''): string
    {
        return $this->get('order_status_receipt_full', $siteId);
    }

    //######################################################################

    /**
     * @inheritDoc
     */
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

    /** @var array<string, array<string, mixed>> */
    private $settings = [];
}
