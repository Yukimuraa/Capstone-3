<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an external user
require_external();

// Get user data for the external user
$user_id = $_SESSION['user_sessions']['external']['user_id'];
$user_name = $_SESSION['user_sessions']['external']['user_name'];
$user_email = isset($_SESSION['user_sessions']['external']['email']) ? $_SESSION['user_sessions']['external']['email'] : 'external123@gmail.com'; // Fallback email

$page_title = "Dashboard - CHMSU BAO";
$base_url = "..";

// Get user profile information
$profile_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_profile = $stmt->get_result()->fetch_assoc();

// Get recent bookings with all columns for debugging
$recent_bookings_query = "SELECT * FROM bookings 
                         WHERE user_id = ? 
                         ORDER BY created_at DESC 
                         LIMIT 5";
$stmt = $conn->prepare($recent_bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_bookings = $stmt->get_result();

// Add hardcoded facility and time data for demonstration
$demo_facility_data = [
    1 => "Basketball Court",
    2 => "Swimming Pool",
    3 => "Gymnasium",
    4 => "Tennis Court"
];

$demo_time_data = [
    1 => "08:30 AM - 03:30 PM",
    2 => "09:30 AM - 03:30 PM",
    3 => "08:30 AM - 01:30 PM",
    4 => "07:30 AM - 05:30 PM"
];

// Get booking statistics
$stats_query = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as approved_bookings,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings,
                SUM(CASE WHEN status = 'cancelled' OR status = 'cancel' THEN 1 ELSE 0 END) as cancelled_bookings
                FROM bookings
                WHERE user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get upcoming bookings
$upcoming_query = "SELECT * FROM bookings 
                  WHERE user_id = ? AND date >= CURDATE() AND (status = 'confirmed' OR status = 'pending')
                  ORDER BY date ASC
                  LIMIT 3";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include '../includes/external_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-6">Dashboard</h1>
                
                <!-- Debug Section - Only visible during development -->
                
                
                <!-- Welcome Banner -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center">
                        <div class="mr-4">
                            <?php if (!empty($user_profile['profile_picture'])): ?>
                                <img src="<?php echo $user_profile['profile_picture']; ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-2xl font-semibold">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Welcome, <?php echo $user_name; ?>!</h2>
                            <p class="text-gray-600">Here's an overview of your gym facility bookings.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Bookings</p>
                                <h3 class="text-2xl font-semibold text-gray-800"><?php echo $stats['total_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Pending</p>
                                <h3 class="text-2xl font-semibold text-gray-800"><?php echo $stats['pending_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Approved</p>
                                <h3 class="text-2xl font-semibold text-gray-800"><?php echo $stats['approved_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                                <i class="fas fa-times-circle text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Rejected/Cancelled</p>
                                <h3 class="text-2xl font-semibold text-gray-800"><?php echo $stats['rejected_bookings'] + $stats['cancelled_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Bookings -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">My Recent Bookings</h2>
                            <a href="requests.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendees</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($recent_bookings->num_rows > 0): ?>
                                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            // Handle empty or null status
                                            $booking_status = !empty($booking['status']) ? strtolower($booking['status']) : 'unknown';
                                            
                                            switch ($booking_status) {
                                                case 'pending':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    $status_text = 'Pending';
                                                    break;
                                                case 'confirmed':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    $status_text = 'Approved';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    $status_text = 'Rejected';
                                                    break;
                                                case 'cancelled':
                                                case 'cancel':
                                                case 'canceled':
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Cancelled';
                                                    break;
                                                case 'unknown':
                                                case '':
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = '';
                                                    break;
                                                default:
                                                    $status_class = 'bg-blue-100 text-blue-800';
                                                    $status_text = ucfirst($booking_status);
                                                    break;
                                            }
                                            
                                            // Format the ID with GYM-YYYY-XXX format
                                            $formatted_id = 'GYM-' . date('Y') . '-' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
                                            
                                            // Get time information
                                            $start_time = "08:30 AM";
                                            $end_time = "03:30 PM";
                                            
                                            // Set specific times based on ID for the example
                                            switch ($booking['id']) {
                                                case 4: // GYM-2025-004
                                                    $start_time = "07:30 AM";
                                                    $end_time = "05:30 PM";
                                                    $purpose = "Other";
                                                    $attendees = 100;
                                                    break;
                                                case 3: // GYM-2025-003
                                                    $start_time = "08:30 AM";
                                                    $end_time = "01:30 PM";
                                                    $purpose = "Conference";
                                                    $attendees = 100;
                                                    break;
                                                case 1: // GYM-2025-001
                                                    $start_time = "08:30 AM";
                                                    $end_time = "03:30 PM";
                                                    $purpose = "Graduation Ceremony";
                                                    $attendees = 100;
                                                    break;
                                                case 2: // GYM-2025-002
                                                    $start_time = "09:30 AM";
                                                    $end_time = "03:30 PM";
                                                    $purpose = "School Program";
                                                    $attendees = 120;
                                                    break;
                                                default:
                                                    $purpose = isset($booking['purpose']) ? $booking['purpose'] : "Event";
                                                    $attendees = isset($booking['attendees']) ? $booking['attendees'] : 100;
                                                    break;
                                            }
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $formatted_id; ?></td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <div class="font-medium">exter</div>
                                                    <div class="text-gray-400">external123@gmail.com</div>
                                                    <div class="text-gray-400">notre</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('F j, Y', strtotime($booking['date'])); ?><br>
                                                    <?php echo $start_time . ' - ' . $end_time; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?php echo $purpose; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $attendees; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="requests.php" class="text-blue-600 hover:text-blue-900">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No recent bookings found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Upcoming Bookings -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Upcoming Bookings</h2>
                        
                        <?php if ($upcoming_bookings->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while ($upcoming = $upcoming_bookings->fetch_assoc()): ?>
                                    <?php
                                    // Format the ID with GYM-YYYY-XXX format
                                    $formatted_id = 'GYM-' . date('Y') . '-' . str_pad($upcoming['id'], 3, '0', STR_PAD_LEFT);
                                    
                                    // Set specific times based on ID for the example
                                    switch ($upcoming['id']) {
                                        case 4: // GYM-2025-004
                                            $start_time = "07:30 AM";
                                            $end_time = "05:30 PM";
                                            $purpose = "Other";
                                            break;
                                        case 3: // GYM-2025-003
                                            $start_time = "08:30 AM";
                                            $end_time = "01:30 PM";
                                            $purpose = "Conference";
                                            break;
                                        case 1: // GYM-2025-001
                                            $start_time = "08:30 AM";
                                            $end_time = "03:30 PM";
                                            $purpose = "Graduation Ceremony";
                                            break;
                                        case 2: // GYM-2025-002
                                            $start_time = "09:30 AM";
                                            $end_time = "03:30 PM";
                                            $purpose = "School Program";
                                            break;
                                        default:
                                            $start_time = "08:30 AM";
                                            $end_time = "03:30 PM";
                                            $purpose = isset($upcoming['purpose']) ? $upcoming['purpose'] : "Event";
                                            break;
                                    }
                                    
                                    $status_badge = '';
                                    if ($upcoming['status'] == 'confirmed') {
                                        $status_badge = '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Approved</span>';
                                    } else {
                                        $status_badge = '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                                    }
                                    ?>
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-semibold text-gray-800"><?php echo $formatted_id; ?></h3>
                                                <p class="text-sm text-gray-600">
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo date('F j, Y', strtotime($upcoming['date'])); ?>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <i class="far fa-clock mr-1"></i>
                                                    <?php echo $start_time . ' - ' . $end_time; ?>
                                                </p>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <i class="far fa-file-alt mr-1"></i>
                                                    <?php echo $purpose; ?>
                                                </p>
                                            </div>
                                            <div>
                                                <?php echo $status_badge; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6">
                                <i class="far fa-calendar-alt text-gray-400 text-4xl mb-2"></i>
                                <p class="text-gray-500">No upcoming bookings</p>
                                <a href="gym.php" class="mt-2 inline-block text-sm text-blue-600 hover:text-blue-800">Book a facility</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menu-button')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>
