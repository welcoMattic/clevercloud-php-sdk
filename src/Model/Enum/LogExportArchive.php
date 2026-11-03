<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * The archive a log export's files are delivered in. Only `targz` is produced
 * today: the API answers 501 for the others until they land.
 */
enum LogExportArchive: string
{
    case TarGz = 'targz';
    case TarZst = 'tarzst';
    case Zip = 'zip';
    case None = 'none';
}
