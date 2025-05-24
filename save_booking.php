<?php
header('Content-Type: application/json');

// Ambil data POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validasi sederhana
$required = ['mentor_id', 'date', 'time', 'full_name', 'email', 'message'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing $field"]);
        exit;
    }
}

// Baca bookings.json
$file = __DIR__ . '/data/bookings.json';
$bookings = [];
if (file_exists($file)) {
    $json = file_get_contents($file);
    $bookings = json_decode($json, true) ?: [];
}

// Tambah booking baru
$bookings[] = [
    'mentor_id' => $data['mentor_id'],
    'date' => $data['date'],
    'time' => $data['time'],
    'full_name' => $data['full_name'],
    'email' => $data['email'],
    'message' => $data['message']
];

// Simpan ke file
file_put_contents($file, json_encode($bookings, JSON_PRETTY_PRINT));

echo json_encode(['success' => true]);