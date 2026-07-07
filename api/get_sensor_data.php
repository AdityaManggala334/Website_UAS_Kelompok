<?php
// api/get_sensor_data.php
// Endpoint untuk mengambil data sensor terbaru (AJAX)

require_once 'koneksi.php';

header('Content-Type: application/json');

$filter = isset($_GET['filter']) ? (int)$_GET['filter'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 60;

// Query data sensor
$query = "SELECT * FROM data_sensor";
if ($filter > 0) {
    $query .= " WHERE id_sensor = $filter";
}
$query .= " ORDER BY waktu_baca DESC LIMIT $limit";

$result = mysqli_query($conn, $query);

$rows = [];
$normal = 0;
$kritis = 0;
$rendah = 0;
$tinggi = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Format waktu
        $row['waktu_baca_formatted'] = date('d M H:i', strtotime($row['waktu_baca']));
        
        if ($row['status'] == 'normal') $normal++;
        elseif ($row['status'] == 'kritis') $kritis++;
        elseif ($row['status'] == 'rendah') $rendah++;
        elseif ($row['status'] == 'tinggi') $tinggi++;
        
        $rows[] = $row;
    }
}

$response = [
    'rows' => $rows,
    'stats' => [
        'total' => count($rows),
        'normal' => $normal,
        'kritis' => $kritis,
        'rendah' => $rendah,
        'tinggi' => $tinggi,
        'time' => date('H:i:s')
    ]
];

echo json_encode($response);
?>