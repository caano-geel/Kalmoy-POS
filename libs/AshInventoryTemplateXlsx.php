<?php
/**
 * Generates formatted inventory import/export .xlsx files (no external deps).
 */
class AshInventoryTemplateXlsx {
    const COL_TEXT = 0;
    const COL_BARCODE = 1;
    const COL_NUMBER_INT = 7;
    const COL_NUMBER_DEC = 5;

    public static function headers(){
        return array(
            'Product Name', 'Barcode', 'Brand', 'Category', 'Variant',
            'Retail Price', 'Unit Cost', 'Quantity', 'Low Stock Alert', 'Status',
        );
    }

    /** @deprecated Use outputSimple() */
    public static function output(){
        self::outputSimple();
    }

    public static function outputSimple(){
        $date = date('Y-m-d');
        $filename = 'Kalmoy_Inventory_Import_Template_' . $date . '.xlsx';
        $rows = array(
            array('Arabica Coffee', '6280000010000', 'Generic', 'Beverages', 'Default', 1000, 650, 25, 5, 'Active'),
            array('Cooking Oil 1L', '6280000010001', 'Popco', 'Cooking Oil', 'Default', 1200, 720, 30, 5, 'Active'),
            array('Milk 500ml', '6280000010002', 'Brookside', 'Dairy & Milk', 'Default', 1000, 650, 50, 5, 'Active'),
            array('Marie Biscuits', '6280000010003', 'Marie', 'Biscuits & Snacks', 'Default', 850, 420, 15, 5, 'Active'),
            array('Colgate Herbal', '01266115', 'Colgate', 'Personal Care', 'Default', 950, 500, 20, 5, 'Active'),
        );
        self::sendWorkbook($filename, 'Kalmoy Inventory Import Template', $rows, self::instructionLines('simple'));
    }

    public static function outputExisting(array $dataRows){
        $date = date('Y-m-d');
        $filename = 'Kalmoy_Existing_Products_Inventory_' . $date . '.xlsx';
        self::sendWorkbook($filename, 'Kalmoy Existing Products Inventory', $dataRows, self::instructionLines('existing'));
    }

    private static function instructionLines($mode){
        $lines = array(
            'INVENTORY IMPORT — INSTRUCTIONS',
            '',
            'BARCODE (column B) must stay TEXT (@). Prefix with apostrophe if needed: \'01266115',
            'Do NOT use number format on barcodes — avoids 6.28E+12 corruption.',
            '',
            'REQUIRED COLUMNS:',
            '  Product Name | Barcode | Brand | Category | Variant',
            '  Retail Price | Unit Cost | Quantity | Low Stock Alert | Status',
            '',
            'UPDATE RULES:',
            '  • Quantity = Available Stock shown in Stock & Inventory',
            '  • Match by barcode first, then product name',
            '  • Existing barcode → updates quantity, price, cost, variant',
            '  • New product → creates product and inventory rows',
            '  • Brand/Category created automatically if missing',
            '  • Status: Active or Inactive',
            '',
            'EXAMPLES:',
            '  Arabica Coffee | 6280000010000 | Generic | Beverages | Default | 1000 | 650 | 25 | 5 | Active',
            '  Colgate Herbal | 01266115 | Colgate | Personal Care | Default | 950 | 500 | 20 | 5 | Active',
            '',
            'COMMON ERRORS:',
            '  • Barcode as 6.28E+12 → format column B as Text',
            '  • Row skipped — barcode belongs to another product',
            '  • Duplicate barcode in file → remove duplicate rows',
            '',
        );
        if($mode === 'simple'){
            $lines[] = 'PURPOSE: Add NEW products — replace the 5 sample rows with your data.';
        }else{
            $lines[] = 'PURPOSE: Bulk UPDATE all existing products — edit values in place.';
        }
        $lines[] = 'Upload: Admin → Stock & Inventory → Import Excel (Import Data sheet only).';
        return $lines;
    }

    private static function sendWorkbook($filename, $title, array $dataRows, array $instructionLines){
        if(!class_exists('ZipArchive')){
            header('HTTP/1.1 500 Internal Server Error');
            exit('ZipArchive is required to generate the Excel template.');
        }
        while(ob_get_level() > 0){
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Length: ' . strlen($bytes = self::buildWorkbookBytes($title, $dataRows, $instructionLines)));
        echo $bytes;
        exit;
    }

    public static function buildWorkbookBytes($title, array $dataRows, array $instructionLines){
        $tmp = tempnam(sys_get_temp_dir(), 'ash_inv_tpl_');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rels());
        $zip->addFromString('docProps/core.xml', self::core($title));
        $zip->addFromString('docProps/app.xml', self::app());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetImport($dataRows));
        $zip->addFromString('xl/worksheets/sheet2.xml', self::sheetInstructions($instructionLines));
        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        return $bytes;
    }

    private static function esc($s){
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function colLetter($index){
        $index = (int)$index;
        $letters = '';
        while($index >= 0){
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26) - 1;
        }
        return $letters;
    }

    private static function columnWidths(array $headers, array $dataRows){
        $maxLens = array();
        foreach($headers as $i => $h){
            $maxLens[$i] = strlen($h);
        }
        foreach($dataRows as $row){
            foreach($row as $i => $val){
                $len = strlen((string)$val);
                if($len > $maxLens[$i]){
                    $maxLens[$i] = $len;
                }
            }
        }
        $mins = array(18, 16, 16, 18, 12, 11, 11, 9, 12, 9);
        $widths = array();
        foreach($maxLens as $i => $len){
            $min = isset($mins[$i]) ? $mins[$i] : 10;
            $widths[] = min(44, max($min, $len + 2));
        }
        return $widths;
    }

    private static function cellStyleForColumn($colIndex){
        if($colIndex === self::COL_BARCODE){
            return 2;
        }
        if(in_array($colIndex, array(5, 6), true)){
            return 4;
        }
        if(in_array($colIndex, array(7, 8), true)){
            return 3;
        }
        return 0;
    }

    private static function isNumericColumn($colIndex){
        return in_array($colIndex, array(5, 6, 7, 8), true);
    }

    private static function dataCell($ref, $colIndex, $value){
        if(self::isNumericColumn($colIndex) && $value !== '' && $value !== null && is_numeric($value)){
            $style = self::cellStyleForColumn($colIndex);
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . self::esc((string)(0 + $value)) . '</v></c>';
        }
        $style = self::cellStyleForColumn($colIndex);
        return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . self::esc((string)$value) . '</t></is></c>';
    }

    private static function headerCell($ref, $value){
        return '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . self::esc($value) . '</t></is></c>';
    }

    private static function contentTypes(){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-officedocument.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';
    }

    private static function rels(){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
    }

    private static function workbookRels(){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private static function workbook(){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="Import Data" sheetId="1" r:id="rId1"/>
<sheet name="Instructions" sheetId="2" r:id="rId2"/>
</sheets>
</workbook>';
    }

    private static function core($title){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>' . self::esc($title) . '</dc:title>
<dc:creator>Kalmoy POS</dc:creator>
</cp:coreProperties>';
    }

    private static function app(){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
<Application>Kalmoy POS</Application>
</Properties>';
    }

    private static function styles(){
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="2">
<numFmt numFmtId="164" formatCode="@"/>
<numFmt numFmtId="165" formatCode="0.00"/>
</numFmts>
<fonts count="3">
<font><sz val="11"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
<font><b/><sz val="12"/><name val="Calibri"/></font>
</fonts>
<fills count="3">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="6">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
</cellXfs>
</styleSheet>';
    }

    private static function sheetImport(array $dataRows){
        $headers = self::headers();
        $widths = self::columnWidths($headers, $dataRows);
        $cols = '';
        foreach($widths as $i => $w){
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $cells = '';
        foreach($headers as $i => $h){
            $cells .= self::headerCell(self::colLetter($i) . '1', $h);
        }
        $rowsXml = '<row r="1" ht="24" customHeight="1">' . $cells . '</row>';
        $r = 2;
        foreach($dataRows as $ex){
            $cells = '';
            foreach($ex as $i => $val){
                $cells .= self::dataCell(self::colLetter($i) . $r, $i, $val);
            }
            $rowsXml .= '<row r="' . $r . '">' . $cells . '</row>';
            $r++;
        }
        $dimension = 'A1:' . self::colLetter(count($headers) - 1) . max(1, $r - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<dimension ref="' . $dimension . '"/>
<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>
<cols>' . $cols . '</cols>
<sheetData>' . $rowsXml . '</sheetData>
</worksheet>';
    }

    private static function sheetInstructions(array $lines){
        $rowsXml = '';
        $r = 1;
        foreach($lines as $line){
            $style = ($r === 1) ? 5 : 0;
            $rowsXml .= '<row r="' . $r . '"><c r="A' . $r . '" t="inlineStr" s="' . $style . '"><is><t>' . self::esc($line) . '</t></is></c></row>';
            $r++;
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetViews><sheetView workbookViewId="0"/></sheetViews>
<cols><col min="1" max="1" width="110" customWidth="1"/></cols>
<sheetData>' . $rowsXml . '</sheetData>
</worksheet>';
    }
}
