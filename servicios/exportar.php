<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit("No autorizado"); }
require_once '../includes/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


$sql = "SELECT s.*, e.nombre as empresa_nombre 
        FROM servicios s
        JOIN empresas e ON s.empresa_id = e.id
        ORDER BY s.fecha DESC";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


$headers = ['A1'=>'Fecha', 'B1'=>'Empresa', 'C1'=>'Equipo', 'D1'=>'Tipo Servicio', 'E1'=>'Horas Uso', 'F1'=>'Descripción', 'G1'=>'Próx. Servicio'];

foreach($headers as $cell => $text){
    $sheet->setCellValue($cell, $text);
    
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}


$row = 2; 
while ($servicio = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($servicio['fecha'])));
    $sheet->setCellValue('B' . $row, $servicio['empresa_nombre']);
    $sheet->setCellValue('C' . $row, $servicio['equipo_atendido'] ?? '-');
    $sheet->setCellValue('D' . $row, $servicio['tipo_servicio'] ?? 'General');
    $sheet->setCellValue('E' . $row, $servicio['horas_uso']);
    $sheet->setCellValue('F' . $row, $servicio['descripcion']);
    
    if($servicio['proximo_servicio']) {
        $sheet->setCellValue('G' . $row, date('d/m/Y', strtotime($servicio['proximo_servicio'])));
    } else {
        $sheet->setCellValue('G' . $row, '-');
    }
    
    $row++;
}

foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'Reporte_Servicios_' . date('Y-m-d') . '.xlsx';

ob_end_clean(); 

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>