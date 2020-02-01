<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 01/02/2020
 */

namespace twentyfourhoursmedia\poll\helper;

class CsvHelper
{

    static function getColumns(array $data) {
        return array_keys($data[0] ?? []);
    }

    const CSV_DEFAULT_OPTS = [
        'write_bom' => true,
        'with_header_row' => true
    ];

    static function createCsv($fh, array $columns, array $data, $opts = self::CSV_DEFAULT_OPTS) {
        $opts = array_merge(self::CSV_DEFAULT_OPTS, $opts);
        if ($opts['write_bom']) {
            fwrite($fh, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        }
        if ($opts['with_header_row']) {
            fputcsv($fh, $columns, ';');
        }
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($columns as $key) {
                $csvRow[] = $row[$key] ?? null;
            }
            fputcsv($fh, $row, ';');
        }
    }

}