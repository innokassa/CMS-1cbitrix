<?php

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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ReceiptAdapterConcrete implements ReceiptAdapterInterface
{
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

        $order = \Bitrix\Sale\Order::load(intval($orderId));

        $basketItems = $order->getBasket()->getBasketItems();
        $items = new ReceiptItemCollection();

        foreach ($basketItems as $basketItem) {
            $itemFields = $basketItem->getFieldValues();

            $productVat = \Bitrix\Catalog\ProductTable::getList(
                [
                    'filter' => [
                        'ID' => $itemFields['PRODUCT_ID']
                    ],
                    'select' => ['VAT_ID'],
                ]
            )->fetch();

            $vatRate = '';
            if ($productVat['VAT_ID'] != 0) {
                $vatRate = intval($itemFields["VAT_RATE"]);
                if ($vatRate > 0 && $subType == ReceiptSubType::PRE) {
                    $vatRate = "$vatRate/1$vatRate";
                }
            } else {
                $vatRate = $this->settings->getVatDefaultItems($siteId);
            }

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
                ->setVat(new Vat($vatRate));

            $items[] = $item;
        }

        $deliveryPrice = $order->getDeliveryPrice();
        if ($deliveryPrice > 0) {
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
                ->setVat(new Vat($this->settings->getVatShipping($siteId)));

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function getTotal(string $orderId, string $siteId): float
    {
        $order = \Bitrix\Sale\Order::load(intval($orderId));
        return $order->getPrice();
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(string $orderId, string $siteId): ?Customer
    {
        $order = \Bitrix\Sale\Order::load(intval($orderId));
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
        $order = \Bitrix\Sale\Order::load(intval($orderId));
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
}
