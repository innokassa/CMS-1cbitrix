<?php

namespace Innokassa\Fiscal\Impl;

use Bitrix\Sale\Order;
use Innokassa\MDK\Entities\Atoms\Vat;
use Innokassa\MDK\Entities\Atoms\Unit;
use Innokassa\MDK\Entities\ReceiptItem;
use Innokassa\MDK\Settings\SettingsAbstract;
use Innokassa\MDK\Entities\Primitives\Notify;
use Innokassa\MDK\Entities\Atoms\PaymentMethod;
use Innokassa\MDK\Entities\Primitives\Customer;
use Innokassa\MDK\Entities\Atoms\ReceiptSubType;
use Innokassa\MDK\Entities\Atoms\ReceiptItemType;
use Innokassa\MDK\Entities\ReceiptAdapterInterface;
use Innokassa\MDK\Collections\ReceiptItemCollection;
use Innokassa\MDK\Exceptions\Base\InvalidArgumentException;

/**
 * Реализация адаптера чеков
 */
class ReceiptAdapterConcrete implements ReceiptAdapterInterface
{
    /**
     * @param SettingsAbstract $settings
     */
    public function __construct(SettingsAbstract $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public function getItems(string $orderId, string $siteId, int $subType): ReceiptItemCollection
    {
        $paymentMethod = null;

        switch ($subType) {
            case ReceiptSubType::PRE:
                $paymentMethod = PaymentMethod::PREPAYMENT_FULL;
                break;
            case ReceiptSubType::FULL:
                $paymentMethod = PaymentMethod::PAYMENT_FULL;
                break;
            default:
                throw new InvalidArgumentException("invalid subType '$subType'");
        }

        $order = $this->getOrder($orderId);

        $basketItems = $order->getBasket()->getBasketItems();
        $items = new ReceiptItemCollection();

        foreach ($basketItems as $basketItem) {
            $itemFields = $basketItem->getFieldValues();

            $item = (new ReceiptItem())
                ->setItemId($itemFields['PRODUCT_ID'])
                ->setName($itemFields["NAME"])
                ->setPrice($itemFields["PRICE"])
                ->setQuantity($itemFields["QUANTITY"])
                ->setPaymentMethod($paymentMethod)
                ->setType(
                    $subType == ReceiptSubType::PRE
                    ? ReceiptItemType::PAYMENT
                    : $this->settings->getTypeDefaultItems($siteId)
                )
                ->setUnit(Unit::DEFAULT)
                ->setVat(
                    $this->getVatProduct(
                        $itemFields['PRODUCT_ID'],
                        $itemFields['VAT_RATE'],
                        $subType,
                        $siteId
                    )
                );

            $items[] = $item;
        }

        $deliveryPrice = $order->getDeliveryPrice();
        if ($deliveryPrice > 0) {
            $vatShipping = $this->getVatShipping($subType, $siteId);
            $item = (new ReceiptItem())
                ->setName('Доставка')
                ->setPrice($deliveryPrice)
                ->setQuantity(1)
                ->setPaymentMethod($paymentMethod)
                ->setUnit(Unit::DEFAULT)
                ->setType(
                    $subType == ReceiptSubType::PRE
                    ? ReceiptItemType::PAYMENT
                    : ReceiptItemType::SERVICE
                )
                ->setVat($vatShipping);

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function getTotal(string $orderId, string $siteId): float
    {
        $order = $this->getOrder($orderId);
        return $order->getPrice();
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(string $orderId, string $siteId): ?Customer
    {
        $order = $this->getOrder($orderId);
        $orderProps = $order->getPropertyCollection()->getArray();

        $customer = null;

        foreach ($orderProps['properties'] as $prop) {
            if ($prop['IS_PROFILE_NAME'] == 'Y') {
                $customer = new Customer();
                $customer->setName(array_shift($prop['VALUE']));
                break;
            }
        }

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getNotify(string $orderId, string $siteId): Notify
    {
        $order = $this->getOrder($orderId);
        $orderProps = $order->getPropertyCollection()->getArray();

        $notify = new Notify();

        foreach ($orderProps['properties'] as $prop) {
            if ($prop['IS_EMAIL'] == 'Y') {
                $notify->setEmail(array_shift($prop['VALUE']));
            } elseif ($prop['IS_PHONE'] == 'Y') {
                $notify->setPhone(array_shift($prop['VALUE']));
            }
        }

        return $notify;
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /** @var Order */
    private $order = null;

    //######################################################################

    /**
     * Получить заказ
     *
     * @param string $orderId
     * @return Order
     */
    private function getOrder(string $orderId): Order
    {
        if ($this->order && $this->order->getId() == $orderId) {
            return $this->order;
        }

        $this->order = Order::load(intval($orderId));

        return $this->order;
    }

    /**
     * Получить НДС на продукцию
     *
     * @param string $productId
     * @param string $vatRate
     * @param integer $subType
     * @param string $siteId
     * @return Vat
     */
    private function getVatProduct(string $productId, string $vatRate, int $subType, string $siteId): Vat
    {
        $productVat = \Bitrix\Catalog\ProductTable::getList(
            [
                'filter' => [
                    'ID' => $productId
                ],
                'select' => ['VAT_ID'],
            ]
        )->fetch();

        $vatRate = '';
        if ($productVat['VAT_ID'] != 0) {
            $vatRate = intval($vatRate);
        } else {
            $vatRate = (new Vat($this->settings->getVatDefaultItems($siteId)))->getName();
        }

        if ($vatRate > 0 && $subType == ReceiptSubType::PRE) {
            $vatRate = "$vatRate/1$vatRate";
        }

        $vat = new Vat($vatRate);

        return $vat;
    }

    /**
     * Получить НДС на доставку
     *
     * @param integer $subType
     * @param string $siteId
     * @return Vat
     */
    private function getVatShipping(int $subType, string $siteId): Vat
    {
        $vat = new Vat($this->settings->getVatShipping($siteId));

        if (($vatRate = $vat->getName()) > 0 && $subType == ReceiptSubType::PRE) {
            $vatRate = "$vatRate/1$vatRate";
            $vat = new Vat($vatRate);
        }

        return $vat;
    }
}
