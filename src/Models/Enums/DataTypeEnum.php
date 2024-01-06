<?php

namespace Eghamat24\DatabaseRepository\Models\Enums;

enum DataTypeEnum
{
    public const INTEGER_TYPE = 'int';
    public const BOOLEAN_TYPE = 'bool';
    public const FLOAT_TYPE = 'float';
    public const STRING_TYPE = 'string';


    case BOOL;
    case BOOLEAN;
    case BIT;
    case INT;
    case INTEGER;
    case TINYINT;
    case SMALLINT;
    case MEDIUMINT;
    case BIGINT;
    case FLOAT;
    case DOUBLE;
    case JSON;
    case CHAR;
    case VARCHAR;
    case BINARY;
    case VARBINARY;
    case TINYBLOB;
    case TINYTEXT;
    case TEXT;
    case BLOB;
    case MEDIUMTEXT;
    case MEDIUMBLOB;
    case LONGTEXT;
    case LONGBLOB;
    case ENUM;
    case DATE;
    case TIME;
    case DATETIME;
    case TIMESTAMP;
    case POINT;

    public function matchingType(): string
    {
        return match ($this) {
            self::BOOL,
            self::BOOLEAN => self::BOOLEAN_TYPE,

            self::BIT,
            self::JSON,
            self::CHAR,
            self::VARCHAR,
            self::BINARY,
            self::VARBINARY,
            self::DATETIME,
            self::TIME,
            self::DATE,
            self::ENUM,
            self::LONGBLOB,
            self::LONGTEXT,
            self::MEDIUMBLOB,
            self::MEDIUMTEXT,
            self::BLOB,
            self::TEXT,
            self::TINYTEXT,
            self::TINYBLOB,
            self::TIMESTAMP,
            self::POINT => self::STRING_TYPE,

            self::INT,
            self::INTEGER,
            self::TINYINT,
            self::SMALLINT,
            self::MEDIUMINT,
            self::BIGINT => self::INTEGER_TYPE,

            self::FLOAT,
            self::DOUBLE => self::FLOAT_TYPE,
        };
    }
}
