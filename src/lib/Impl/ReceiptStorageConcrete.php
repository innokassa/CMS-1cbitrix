<?php

namespace Innokassa\Fiscal\Impl;

use Innokassa\MDK\Entities\Receipt;
use Innokassa\MDK\Storage\ReceiptFilter;
use Innokassa\MDK\Entities\ConverterAbstract;
use Innokassa\MDK\Collections\ReceiptCollection;
use Innokassa\MDK\Storage\ReceiptStorageInterface;

/**
 * Реализация хранилища чеков
 */
class ReceiptStorageConcrete implements ReceiptStorageInterface
{
    /**
     * @param \CDatabase $db
     * @param ConverterAbstract $converter
     */
    public function __construct(\CDatabase $db, ConverterAbstract $converter)
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
                sprintf('DB error (%s:%s)', __FILE__, __LINE__),
                false
            );

            return $receipt->getId();
        }

        $this->db->Insert(
            self::$table,
            $a,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__),
            false
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
            } elseif (is_array($val)) {
                $val = '(' . implode(',', $val) . ')';

                if ($value['op'] == '=') {
                    $value['op'] = ' IN ';
                } else {
                    $value['op'] = ' NOT IN ';
                }
            } else {
                $val = "'$val'";
            }
            $op = $value['op'];
            $aWhere2[] = "{$key}{$op}$val";
        }

        $where = implode(' AND ', $aWhere2);

        $res = $this->db->Query(
            sprintf(
                'SELECT * FROM `%s` WHERE %s ORDER BY `id` ASC %s',
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

    /**
     * @inheritDoc
     */
    public function min(ReceiptFilter $filter, string $column)
    {
        $where = $this->where($filter);

        $res = $this->db->Query(
            sprintf(
                'SELECT MIN(%s) FROM `%s` WHERE %s',
                $column,
                self::$table,
                $where
            ),
            false,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__)
        );

        $result = $res->Fetch();
        return current($result);
    }

    /**
     * @inheritDoc
     */
    public function max(ReceiptFilter $filter, string $column)
    {
        $where = $this->where($filter);

        $res = $this->db->Query(
            sprintf(
                'SELECT MAX(%s) FROM `%s` WHERE %s',
                $column,
                self::$table,
                $where
            ),
            false,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__)
        );

        $result = $res->Fetch();
        return current($result);
    }

    /**
     * @inheritDoc
     */
    public function count(ReceiptFilter $filter): int
    {
        $where = $this->where($filter);

        $res = $this->db->Query(
            sprintf(
                'SELECT COUNT(*) FROM `%s` WHERE %s',
                self::$table,
                $where
            ),
            false,
            sprintf('DB error (%s:%s)', __FILE__, __LINE__)
        );

        $result = $res->Fetch();
        return current($result);
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /** @var \CDatabase */
    private $db = null;

    /** @var ConverterAbstract */
    private $converter = null;

    /** @var string */
    private static $table = 'innokassa_fiscal';

    //######################################################################

    /**
     * Обработка массива данных для передачи в SQL запрос
     *
     * @param array $a
     * @return array
     */
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

    private function where(ReceiptFilter $filter): string
    {
        $aWhere = $filter->toArray();
        $aWhere2 = [];
        foreach ($aWhere as $key => $value) {
            $val = $value['value'];
            if ($val === null) {
                $val = 'null';
            } elseif (is_array($val)) {
                $val = '(' . implode(',', $val) . ')';

                if ($value['op'] == '=') {
                    $value['op'] = ' IN ';
                } else {
                    $value['op'] = ' NOT IN ';
                }
            } else {
                $val = "'$val'";
            }
            $op = $value['op'];
            $aWhere2[] = "{$key}{$op}$val";
        }

        $where = implode(' AND ', $aWhere2);
        return $where;
    }
}
