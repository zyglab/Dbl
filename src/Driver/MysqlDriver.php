<?php

declare(strict_types=1);

namespace Dbl\Driver;

use Dbl\Collection;
use Dbl\Column;
use Dbl\Cast\DatetimeCast;
use Dbl\Cast\IntegerCast;
use Dbl\Cast\JsonCast;
use Dbl\Database;
use Dbl\Exception\Exception;

class MysqlDriver extends Driver
{
    /**
     * @var array
     */
    protected $castingMap = [
        'int' => IntegerCast::class,
        'timestamp' => DatetimeCast::class,
        'json' => JsonCast::class,
    ];

    /**
     * @throws Exception
     *
     * @return Collection
     */
    public function getColumns(): Collection
    {
        $cachableTableName = str_replace([' ', '-', '.'], '_', $this->getTableName());
        $cacheKey = sprintf('__dbl_mysql_%s_columns', $cachableTableName);

        $columnsInfo = Database::getInstance()->cache(
            $cacheKey,
            function (): array {
                $query = 'SHOW COLUMNS FROM ' . $this->getTableName();
                $columns = Database::getInstance()->fetchAll($query)->raw();

                foreach ($columns as $k => $v) {
                    $columns[$k] = $v->raw();
                }

                return $columns;
            }
        );

        $columns = new Collection();

        foreach ($columnsInfo as $info) {
            $type = $info['Type'];
            $length = null;

            if (preg_match('/([a-z]+)\((\d+)\)/', $type, $matches)) {
                $type = $matches[1];
                $length = (int) $matches[2];
            }

            $columns[] = new Column(
                $info['Field'],
                $type,
                $info['Null'] === 'YES' ? true : false,
                $length,
                $info
            );
        }

        return $columns;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table->table;
    }
}
