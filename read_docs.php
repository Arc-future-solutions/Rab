<?php

function readDocxText($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml) {
            $dom = new DOMDocument();
            @$dom->loadXML($xml);
            return strip_tags($dom->saveXML());
        }
    }
    return false;
}

function readXlsxText($filename) {
    $zip = new ZipArchive();
    $text = "";
    if ($zip->open($filename) === TRUE) {
        // Read shared strings
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $sharedStrings = [];
        if ($sharedStringsXml) {
            $dom = new DOMDocument();
            @$dom->loadXML($sharedStringsXml);
            $strings = $dom->getElementsByTagName('t');
            foreach ($strings as $s) {
                $sharedStrings[] = $s->nodeValue;
            }
        }
        
        // Read sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml) {
            $dom = new DOMDocument();
            @$dom->loadXML($sheetXml);
            $cells = $dom->getElementsByTagName('v');
            foreach ($cells as $c) {
                $val = $c->nodeValue;
                if (is_numeric($val) && isset($sharedStrings[(int)$val])) {
                    $text .= $sharedStrings[(int)$val] . "\n";
                } else {
                    $text .= $val . "\n";
                }
            }
        }
        $zip->close();
    }
    return $text;
}

echo "=== DOCX SUMMARY ===\n";
$docx = readDocxText(__DIR__ . '/RAB_Assessment_Platform_Master_Developer_Handover.docx');
// Just output the first 2000 chars and last 2000 to get a sense
echo substr($docx, 0, 4000) . "\n...\n";

echo "\n=== XLSX SUMMARY ===\n";
$xlsx = readXlsxText(__DIR__ . '/RAB_PHI_ITSM_Consultant_Tool.xlsx');
echo substr($xlsx, 0, 4000);
