<?php
/**
 * Minimal XLSX reader (first worksheet, shared strings + inline strings).
 */
class AshSimpleXlsx {
    public static function readRows($filepath){
        if(!class_exists('ZipArchive') || !is_file($filepath)){
            return false;
        }
        $zip = new ZipArchive();
        if($zip->open($filepath) !== true){
            return false;
        }
        $shared = array();
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if($ssXml !== false){
            $ss = @simplexml_load_string($ssXml);
            if($ss && isset($ss->si)){
                foreach($ss->si as $si){
                    if(isset($si->t)){
                        $shared[] = (string)$si->t;
                    }elseif(isset($si->r)){
                        $text = '';
                        foreach($si->r as $r){
                            $text .= (string)$r->t;
                        }
                        $shared[] = $text;
                    }else{
                        $shared[] = '';
                    }
                }
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if($sheetXml === false){
            return false;
        }
        $sheet = @simplexml_load_string($sheetXml);
        if(!$sheet || !isset($sheet->sheetData->row)){
            return array();
        }
        $rows = array();
        foreach($sheet->sheetData->row as $row){
            $cells = array();
            $colIndex = 0;
            foreach($row->c as $c){
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)/', $ref, $m);
                $letters = isset($m[1]) ? $m[1] : '';
                $idx = self::columnIndex($letters);
                while($colIndex < $idx){
                    $cells[] = '';
                    $colIndex++;
                }
                $cells[] = self::cellValue($c, $shared);
                $colIndex++;
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    private static function cellValue($c, $shared){
        $type = (string)$c['t'];
        if($type === 'inlineStr' && isset($c->is->t)){
            return (string)$c->is->t;
        }
        if($type === 'str' && isset($c->v)){
            return (string)$c->v;
        }
        $val = isset($c->v) ? (string)$c->v : '';
        if($type === 's' && $val !== '' && isset($shared[(int)$val])){
            return $shared[(int)$val];
        }
        return $val;
    }

    private static function columnIndex($letters){
        $letters = strtoupper($letters);
        $n = 0;
        $len = strlen($letters);
        for($i = 0; $i < $len; $i++){
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $n - 1);
    }
}
