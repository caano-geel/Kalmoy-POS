<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class AshPdfExport {
    public static function send($filename, $title, $html){
        while(ob_get_level() > 0){
            ob_end_clean();
        }
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $wrapped = self::wrapHtml($title, $html);
        $dompdf->loadHtml($wrapped);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . AshSpreadsheetExport::safeFilename(preg_replace('/\\.xlsx$/i', '.pdf', $filename)) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $dompdf->output();
        exit;
    }

    public static function printPage($title, $html){
        while(ob_get_level() > 0){
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo self::wrapHtml($title, $html, true);
        echo '<script>window.onload=function(){window.print();};</script>';
        exit;
    }

    public static function tableHtml($title, $subtitle, array $headers, array $rows, array $footer = array()){
        $html = '<h2>' . htmlspecialchars($title) . '</h2>';
        if($subtitle !== ''){
            $html .= '<p class="sub">' . htmlspecialchars($subtitle) . '</p>';
        }
        $html .= '<table><thead><tr>';
        foreach($headers as $h){
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach($rows as $row){
            $html .= '<tr>';
            foreach($headers as $i => $h){
                $val = isset($row[$i]) ? $row[$i] : '';
                $html .= '<td>' . htmlspecialchars((string)$val) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        if(!empty($footer)){
            $html .= '<tfoot><tr>';
            foreach($footer as $cell){
                $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $html .= '</tr></tfoot>';
        }
        $html .= '</table>';
        return $html;
    }

    private static function wrapHtml($title, $body, $forPrint = false){
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title><style>
        body{font-family:DejaVu Sans,Times New Roman,serif;font-size:11px;color:#222;margin:24px;}
        h2{margin:0 0 6px;font-size:18px;}
        .sub{color:#666;margin:0 0 14px;font-size:12px;}
        table{width:100%;border-collapse:collapse;margin-top:8px;}
        th,td{border:1px solid #ccc;padding:5px 6px;text-align:left;vertical-align:top;}
        th{background:#1f4e79;color:#fff;font-weight:bold;}
        tr:nth-child(even) td{background:#f8f9fa;}
        tfoot td{font-weight:bold;background:#eef2f7;}
        </style></head><body>' . $body . '</body></html>';
    }
}
