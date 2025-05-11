<?php
// Use staff-specific session
session_name('bao_staff_session');
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a staff
require_staff();

// Rest of your dashboard code...
// Include header, content, footer, etc.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - CHMSU BAO</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include '../includes/staff_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-6">Staff Dashboard</h1>
                
                <!-- Dashboard Content -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">Bus Bookings</h2>
                            <i class="fas fa-bus text-blue-500"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">4</p>
                        <p class="text-sm text-gray-500">Upcoming this week</p>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">Pending Requests</h2>
                            <i class="fas fa-clipboard-list text-green-500"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">7</p>
                        <p class="text-sm text-gray-500">Need your attention</p>
                    </div>
                    
                    <!-- Card 3 -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">Completed Tasks</h2>
                            <i class="fas fa-check-circle text-purple-500"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">23</p>
                        <p class="text-sm text-gray-500">This month</p>
                    </div>
                </div>
                
                <!-- Staff Dashboard Content -->
                <!-- Add your staff-specific dashboard content here -->
            </main>
        </div>
    </div>
</body>
</html>
