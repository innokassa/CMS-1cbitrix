<?php

use Innokassa\MDK\Entities\Receipt;
use Innokassa\MDK\Storage\ReceiptFilter;
use Innokassa\MDK\Entities\ConverterAbstract;
use Innokassa\MDK\Collections\ReceiptCollection;
use Innokassa\MDK\Storage\ReceiptStorageInterface;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ReceiptStorageConcrete implements ReceiptStorageInterface
{
    public function __construct(CDatabase $db, ConverterAbstract $converter)
    {
        $this->db = $db;
        $this->converter = $converter;
    }

    /**
     * @inheritDoc
     */
    public function save(Receipt $receipt): int
    {
        $a = $this->converter->receiptToArray($receipt);
        $a = static::escapeArr($a);

        if ($receipt->getId() != 0) {
            unset($a['id']);

            $this->db->Update(
                self::$table,
                $a,
                sprintf('WHERE `id`=%d', $receipt->getId()),
                sprintf('DB error (%s:%s)', __FILE__, __LINE__)
            );

            return $receipt->getId();
        }

        $this->db->Insert(
            self::$table,
            $a,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__),
            true
        );

        $id = $this->db->LastID();
        $receipt->setId($id);

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function getOne(int $id): ?Receipt
    {
        $res = $this->db->Query(
            sprintf('SELECT * FROM `%s` WHERE `id`=%d', self::$table, $id),
            false,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__)
        );

        if ($a = $res->Fetch()) {
            return $this->converter->receiptFromArray($a);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getCollection(ReceiptFilter $filter, int $limit = 0): ReceiptCollection
    {
        $aWhere = $filter->toArray();
        $aWhere2 = [];
        foreach ($aWhere as $key => $value) {
            $val = $value['value'];
            if ($val === null) {
                $val = 'null';
            } else {
                $val = "'$val'";
            }
            $op = $value['op'];
            $aWhere2[] = "{$key}{$op}$val";
        }

        $where = implode(' AND ', $aWhere2);

        $res = $this->db->Query(
            sprintf(
                'SELECT * FROM `%s` WHERE %s %s',
                self::$table,
                $where,
                ($limit > 0 ? "LIMIT $limit" : '')
            ),
            false,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__)
        );
        $receipts = new ReceiptCollection();

        while ($a = $res->Fetch()) {
            $a['items'] = json_decode($a['items'], true);
            $a['amount'] = json_decode($a['amount'], true);
            $a['customer'] = json_decode($a['customer'], true);
            $a['notify'] = json_decode($a['notify'], true);

            $receipt = $this->converter->receiptFromArray($a);
            $receipts[] = $receipt;
        }

        return $receipts;
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /** @var CDatabase */
    private $db = null;

    /** @var ConverterAbstract */
    private $converter = null;

    /** @var string */
    private static $table = 'innokassa_fiscal';

    //######################################################################

    protected function escapeArr(array $a): array
    {
        foreach ($a as $key => $value) {
            if (is_string($value)) {
                $a[$key] = sprintf("'%s'", $this->db->ForSql($value));
            } elseif (is_array($value)) {
                $a[$key] = sprintf("'%s'", $this->db->ForSql(json_encode($value, JSON_UNESCAPED_UNICODE)));
            }
        }

        return $a;
    }
}
