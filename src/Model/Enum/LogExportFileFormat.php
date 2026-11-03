<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * The shape of each file inside a log export archive. Only `json` is produced
 * today: the API answers 501 for the others until they land.
 */
enum LogExportFileFormat: string
{
    case Json = 'json';
    case Csv = 'csv';
    case Avro = 'avro';
    case Parquet = 'parquet';
}
