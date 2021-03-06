<?php

declare(strict_types=1);

namespace Dbl\Cast;

use DateTime;
use Dbl\Column;
use Exception;

class DatetimeCast implements Cast
{
    /**
     * @param mixed $value
     * @param Column $column
     *
     * @return DateTime
     *
     * @throws Exception
     */
    public static function code($value, Column $column): ?DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        if (is_string($value)) {
            return new DateTime($value);
        }

        return null;
    }

    /**
     * @param mixed $value
     * @param Column $column
     *
     * @return string|null
     */
    public static function database($value, Column $column): ?string
    {
        if (is_null($value) && $column->null) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
}
