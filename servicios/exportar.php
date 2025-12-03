<?php
session_start();
require_once '../includes/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet; //esto me genero bastantes problemas por la extension, tuve que cambiar el php.ini etc
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$sql = "SELECT s.*, e.nombre as empresa_nombre 
        FROM servicios s
        JOIN empresas e ON s.empresa_id = e.id
        ORDER BY s.fecha DESC";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


$sheet->setCellValue('A1', 'Fecha');
$sheet->setCellValue('B1', 'Empresa');
$sheet->setCellValue('C1', 'Descripción');


$row = 2; 
while ($servicio = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($servicio['fecha'])));
    $sheet->setCellValue('B' . $row, htmlspecialchars($servicio['empresa_nombre']));
    $sheet->setCellValue('C' . $row, htmlspecialchars($servicio['descripcion']));
    $row++;
}


$writer = new Xlsx($spreadsheet);
$filename = 'servicios_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');


$writer->save('php://output');
exit;
?>
