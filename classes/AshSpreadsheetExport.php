<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AshSpreadsheetExport {
    public static function send($filename, array $headers, array $rows, array $options = array()){
        while(ob_get_level() > 0){
            ob_end_clean();
        }
        $spreadsheet = self::build($headers, $rows, $options);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . self::safeFilename($filename) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    public static function build(array $headers, array $rows, array $options = array()){
        $sheetTitle = isset($options['sheet_title']) ? $options['sheet_title'] : 'Export';
        $textCols = isset($options['text_cols']) ? $options['text_cols'] : array();
        $moneyCols = isset($options['money_cols']) ? $options['money_cols'] : array();
        $dateCols = isset($options['date_cols']) ? $options['date_cols'] : array();
        $intCols = isset($options['int_cols']) ? $options['int_cols'] : array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::safeSheetTitle($sheetTitle));

        $colCount = count($headers);
        foreach($headers as $i => $label){
            $col = $i + 1;
            $sheet->setCellValueByColumnAndRow($col, 1, $label);
        }
        $sheet->getStyle('A1:' . self::colLetter($colCount) . '1')->applyFromArray(array(
            'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF')),
            'fill' => array('fillType' => Fill::FILL_SOLID, 'startColor' => array('rgb' => '1F4E79')),
            'alignment' => array('horizontal' => Alignment::HORIZONTAL_CENTER),
        ));

        $rowNum = 2;
        foreach($rows as $row){
            foreach($headers as $i => $label){
                $col = $i + 1;
                $value = isset($row[$i]) ? $row[$i] : '';
                $cell = $sheet->getCellByColumnAndRow($col, $rowNum);
                if(in_array($i, $textCols, true)){
                    $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                    $cell->getStyle()->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                }elseif(in_array($i, $moneyCols, true) && $value !== '' && $value !== null && is_numeric($value)){
                    $cell->setValue((float)$value);
                    $cell->getStyle()->getNumberFormat()->setFormatCode('"Ksh "#,##0.00');
                }elseif(in_array($i, $intCols, true) && $value !== '' && $value !== null && is_numeric($value)){
                    $cell->setValue((int)$value);
                    $cell->getStyle()->getNumberFormat()->setFormatCode('0');
                }elseif(in_array($i, $dateCols, true) && !empty($value)){
                    $ts = strtotime((string)$value);
                    if($ts){
                        $cell->setValue(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts));
                        $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm');
                    }else{
                        $cell->setValue((string)$value);
                    }
                }else{
                    $cell->setValue($value);
                }
            }
            $rowNum++;
        }

        for($i = 1; $i <= $colCount; $i++){
            $sheet->getColumnDimension(self::colLetter($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . self::colLetter($colCount) . max(1, $rowNum - 1));
        return $spreadsheet;
    }

    public static function safeFilename($name){
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$name);
        return $name !== '' ? $name : 'Kalmoy_Export.xlsx';
    }

    public static function safeSheetTitle($title){
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', (string)$title);
        $title = trim($title);
        if($title === '') $title = 'Export';
        return mb_substr($title, 0, 31);
    }

    public static function colLetter($index){
        $letters = '';
        while($index > 0){
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }
        return $letters !== '' ? $letters : 'A';
    }
}
