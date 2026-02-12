<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Location.php';

$db = Database::getInstance()->getConnection();
$userModel = new User();
$locationModel = new Location();

echo "Seeding database...\n";

// 1. Create a Vendor (if not exists)
$vendorEmail = 'vendor@city.com';
if(!$userModel->isEmailTaken($vendorEmail)) {
    $vendorId = $userModel->create([
        'full_name' => 'City Infrastructure Ltd',
        'email' => $vendorEmail,
        'password' => 'vendor123',
        'phone' => '9800000000',
        'role' => 'vendor'
    ]);
    echo "Created Vendor ID: $vendorId\n";
} else {
    $vendor = $userModel->validateCredentials($vendorEmail, 'vendor123');
    $vendorId = $vendor['id'];
    echo "Using Vendor ID: $vendorId\n";
}

// 2. Clear old test locations (optional - safer not to delete production data but for this demo ok)
// $db->exec("DELETE FROM locations");

// 3. Seed "Smart" Locations
$locations = [
    [
        'name' => 'Hetauda City Center Hub',
        'address' => 'Main Road, Hetauda',
        'description' => 'Central automated parking tower.',
        'price_per_hour' => 50.00,
        'total_slots' => 100,
        'latitude' => 27.4293,
        'longitude' => 85.0305
    ],
    [
        'name' => 'Bus Park Smart Zone',
        'address' => 'Bus Park, Hetauda',
        'description' => 'EV charging enabled spots.',
        'price_per_hour' => 30.00,
        'total_slots' => 45,
        'latitude' => 27.4200,
        'longitude' => 85.0200
    ],
    [
        'name' => 'Huprachaur Recreational',
        'address' => 'Huprachaur, Hetauda',
        'description' => 'Secure night parking available.',
        'price_per_hour' => 25.00,
        'total_slots' => 60,
        'latitude' => 27.4350,
        'longitude' => 85.0400
    ]
];

foreach ($locations as $data) {
    // Check duplication strictly for this seed script
    $stmt = $db->prepare("SELECT id FROM locations WHERE name = ?");
    $stmt->execute([$data['name']]);
    if($stmt->rowCount() == 0) {
        $id = $locationModel->create($vendorId, $data);
        $locationModel->approveLocation($id); // Auto approve for demo
        echo "Created & Approved Location: {$data['name']}\n";
    } else {
        echo "Skipped existing: {$data['name']}\n";
    }
}

echo "Database seeded successfully!\n";
?>
