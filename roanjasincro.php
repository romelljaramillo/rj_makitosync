<?php

$xml = simplexml_load_file(dirname(__FILE__) . "/import/2021-02-18-ItemPrintingFile.xml");

echo '<table border=2>';
// echo '<tr>';
// foreach ($xml->product as $node) {
//     foreach ($node as $key1 => $node1) {
//         foreach ($node1->printjobs->printjob as $key2 => $node2) {
//             foreach ($node2 as $key3 => $node3) {
//                 echo '<th>' . $key3 . '</th>';
//             }
//             echo '<th>' . $key2 . '</th>';
//         }
//         // break;
//         echo '<th>' . $key1 . '</th>';
//     }

//     break;
// }
// echo '</tr>';

foreach ($xml->product as $node) {
    $refProduct = $node->ref;
    $nameProduct = $node->name;
    foreach ($node->printjobs->printjob as $node1) {
        $teccode = $node1->teccode;
        $tecname = $node1->tecname;
        $maxcolour = $node1->maxcolour;
        $includedcolour = $node1->includedcolour;
        echo '<tr>';
        foreach ($node1->areas->area as $v) {
            echo '<td>' . $refProduct . '</td>';
            echo '<td>' . $nameProduct . '</td>';
            echo '<td>' . $teccode . '</td>';
            echo '<td>' . $tecname . '</td>';
            echo '<td>' . $maxcolour . '</td>';
            echo '<td>' . $includedcolour . '</td>';
            echo '<td>' . $v->areacode . '</td>';
            echo '<td>' . $v->areaname . '</td>';
            echo '<td>' . $v->areawidth . '</td>';
            echo '<td>' . $v->areahight . '</td>';
            echo '<td>' . $v->areaimg . '</td>';
            echo '</tr>';
        }
    }
}
echo '</table>';



