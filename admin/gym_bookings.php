<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get user data for the admin
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$page_title = "Gym Reservations Management - CHMSU BAO";
$base_url = "..";

// Handle reservation status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = sanitize_input($_POST['booking_id'] ?? '');
    $action = sanitize_input($_POST['action']);
    $admin_remarks = sanitize_input($_POST['admin_remarks'] ?? '');
    
    if (!empty($booking_id)) {
        // Check if reservation exists
        $check_stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND facility_type = 'gym'");
        $check_stmt->bind_param("s", $booking_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $booking = $check_result->fetch_assoc();
            $new_status = '';
            
            switch ($action) {
                case 'approve':
                    $new_status = 'confirmed';
                    break;
                case 'reject':
                    $new_status = 'rejected';
                    break;
                case 'cancel':
                    $new_status = 'cancelled';
                    break;
                default:
                    $_SESSION['error'] = "Invalid action";
                    header("Location: gym_bookings.php");
                    exit();
            }
            
            // Update reservation status
            $update_stmt = $conn->prepare("UPDATE bookings SET status = ?, additional_info = JSON_SET(COALESCE(additional_info, '{}'), '$.admin_remarks', ?) WHERE booking_id = ?");
            $update_stmt->bind_param("sss", $new_status, $admin_remarks, $booking_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Reservation has been " . ucfirst($action) . "d successfully.";
                
                // TODO: Send notification to user about reservation status change
                
            } else {
                $_SESSION['error'] = "Error updating reservation: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Reservation not found";
        }
    } else {
        $_SESSION['error'] = "Invalid reservation ID";
    }
    
    // Redirect to prevent form resubmission
    header("Location: gym_bookings.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Build query based on filters
$query = "SELECT b.*, u.name as user_name, u.email as user_email, u.organization 
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          WHERE b.facility_type = 'gym'";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    $query .= " AND b.date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($user_filter > 0) {
    $query .= " AND b.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

$query .= " ORDER BY b.date DESC, b.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings_result = $stmt->get_result();

// Get external users for filter dropdown
$users_query = "SELECT id, name, email FROM users WHERE user_type = 'external' ORDER BY name";
$users_result = $conn->query($users_query);
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Gym Reservations Management</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $user_name; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" id="menu-button">
                        <span class="sr-only">Open menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div class="max-w-7xl mx-auto">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $_SESSION['success']; ?></p>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $_SESSION['error']; ?></p>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <form action="gym_bookings.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <select id="user_id" name="user_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                <option value="">All Users</option>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter === (int)$user['id'] ? 'selected' : ''; ?>>
                                        <?php echo $user['name']; ?> (<?php echo $user['email']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="md:col-span-3 flex items-center">
                            <button type="submit" class="bg-emerald-600 text-white py-2 px-4 rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            <a href="gym_bookings.php" class="ml-2 bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                <i class="fas fa-sync-alt mr-1"></i> Reset
                            </a>
                            <a href="gym_management.php" class="ml-auto bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-cog mr-1"></i> Manage Facilities
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Reservations Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Gym Reservations</h3>
                            <p class="mt-1 text-sm text-gray-500">Manage all gym facility reservations</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            Total: <span class="font-semibold"><?php echo $bookings_result->num_rows; ?></span> reservations
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendees</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <!-- <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th> -->
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($bookings_result->num_rows > 0): ?>
                                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                        <?php 
                                        $status_class = '';
                                        $status_text = ucfirst($booking['status']);
                                        
                                        switch ($booking['status']) {
                                            case 'pending':
                                                $status_class = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'confirmed':
                                                $status_class = 'bg-green-100 text-green-800';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-red-100 text-red-800';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-gray-100 text-gray-800';
                                                break;
                                        }
                                        
                                        // Check if reservation can be managed
                                        $can_manage = false;
                                        $booking_date = new DateTime($booking['date']);
                                        $today = new DateTime();
                                        $today->setTime(0, 0, 0);
                                        
                                        if ($booking['status'] === 'pending' || ($booking['status'] === 'confirmed' && $booking_date >= $today)) {
                                            $can_manage = true;
                                        }
                                        
                                        // Parse additional info
                                        $additional_info = json_decode($booking['additional_info'], true) ?: [];
                                        $admin_remarks = $additional_info['admin_remarks'] ?? '';
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $booking['booking_id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $booking['user_name']; ?>
                                                <?php if (!empty($booking['organization'])): ?>
                                                    <div class="text-xs text-gray-400"><?php echo $booking['organization']; ?></div>
                                                <?php endif; ?>
                                                <div class="text-xs text-gray-400"><?php echo $booking['user_email']; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('F j, Y', strtotime($booking['date'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate"><?php echo $booking['purpose']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $booking['attendees']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button type="button" onclick="openViewModal(<?php echo htmlspecialchars(json_encode([
                                                    'booking_id' => $booking['booking_id'],
                                                    'user_name' => $booking['user_name'],
                                                    'organization' => $booking['organization'],
                                                    'date' => $booking['date'],
                                                    'start_time' => $booking['start_time'],
                                                    'end_time' => $booking['end_time'],
                                                    'purpose' => $booking['purpose'],
                                                    'attendees' => $booking['attendees'],
                                                    'status' => $booking['status'],
                                                    'admin_remarks' => $admin_remarks,
                                                    'created_at' => $booking['created_at'],
                                                    'additional_info' => $booking['additional_info']
                                                ])); ?>)" class="text-blue-600 hover:text-blue-900 mr-2">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($can_manage): ?>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <button type="button" onclick="openApproveModal('<?php echo $booking['booking_id']; ?>')" class="text-green-600 hover:text-green-900 mr-2">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" onclick="openRejectModal('<?php echo $booking['booking_id']; ?>')" class="text-red-600 hover:text-red-900 mr-2">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                                        <button type="button" onclick="openCancelModal('<?php echo $booking['booking_id']; ?>')" class="text-gray-600 hover:text-gray-900">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No reservations found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- View Reservation Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Reservation Details</h3>
            <button type="button" onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Reservation ID</p>
                    <p id="view-booking-id" class="text-base text-gray-900"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Status</p>
                    <p id="view-status" class="text-base"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Date</p>
                    <p id="view-date" class="text-base text-gray-900"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Time</p>
                    <p id="view-time" class="text-base text-gray-900"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">User</p>
                    <p id="view-user" class="text-base text-gray-900"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Attendees</p>
                    <p id="view-attendees" class="text-base text-gray-900"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-500">Purpose</p>
                    <p id="view-purpose" class="text-base text-gray-900"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-500">Admin Remarks</p>
                    <p id="view-remarks" class="text-base text-gray-900"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Created At</p>
                    <p id="view-created" class="text-base text-gray-900"></p>
                </div>
                <div id="additional-info-container" class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-500">Additional Information</p>
                    <div id="additional-info" class="text-base text-gray-900 space-y-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Reservation Modal -->
<div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Approve Reservation</h3>
            <button type="button" onclick="closeModal('approveModal')" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="gym_bookings.php" method="POST" class="mt-4">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" id="approve-booking-id" name="booking_id" value="">
            
            <div class="mb-4">
                <label for="approve-remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks (Optional)</label>
                <textarea id="approve-remarks" name="admin_remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Add any notes or instructions for the user"></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('approveModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Approve Reservation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Reservation Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Reject Reservation</h3>
            <button type="button" onclick="closeModal('rejectModal')" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="gym_bookings.php" method="POST" class="mt-4">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" id="reject-booking-id" name="booking_id" value="">
            
            <div class="mb-4">
                <label for="reject-remarks" class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                <textarea id="reject-remarks" name="admin_remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Explain why the reservation is being rejected" required></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Reject Reservation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Reservation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Cancel Reservation</h3>
            <button type="button" onclick="closeModal('cancelModal')" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="gym_bookings.php" method="POST" class="mt-4">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" id="cancel-booking-id" name="booking_id" value="">
            
            <div class="mb-4">
                <label for="cancel-remarks" class="block text-sm font-medium text-gray-700 mb-1">Reason for Cancellation</label>
                <textarea id="cancel-remarks" name="admin_remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500" placeholder="Explain why the reservation is being cancelled" required></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('cancelModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Back
                </button>
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Cancel Reservation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Modal functions
    function openViewModal(booking) {
        document.getElementById('view-booking-id').textContent = booking.booking_id;
        
        const statusElement = document.getElementById('view-status');
        statusElement.textContent = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
        
        // Set status color
        statusElement.className = 'text-base';
        if (booking.status === 'pending') {
            statusElement.classList.add('text-yellow-600');
        } else if (booking.status === 'confirmed') {
            statusElement.classList.add('text-green-600');
        } else if (booking.status === 'rejected') {
            statusElement.classList.add('text-red-600');
        } else {
            statusElement.classList.add('text-gray-600');
        }
        
        document.getElementById('view-date').textContent = new Date(booking.date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('view-time').textContent = formatTime(booking.start_time) + ' - ' + formatTime(booking.end_time);
        document.getElementById('view-user').textContent = booking.user_name + (booking.organization ? ` (${booking.organization})` : '');
        document.getElementById('view-attendees').textContent = booking.attendees;
        document.getElementById('view-purpose').textContent = booking.purpose;
        document.getElementById('view-remarks').textContent = booking.admin_remarks || 'No remarks';
        document.getElementById('view-created').textContent = new Date(booking.created_at).toLocaleString();
        
        // Display additional information if available
        const additionalInfoContainer = document.getElementById('additional-info-container');
        const additionalInfoElement = document.getElementById('additional-info');
        additionalInfoElement.innerHTML = '';
        
        try {
            let additionalInfo = booking.additional_info;
            if (typeof additionalInfo === 'string') {
                additionalInfo = JSON.parse(additionalInfo);
            }
            
            if (additionalInfo && typeof additionalInfo === 'object') {
                let hasInfo = false;
                
                for (const [key, value] of Object.entries(additionalInfo)) {
                    if (key !== 'admin_remarks' && value) {
                        hasInfo = true;
                        const infoItem = document.createElement('p');
                        infoItem.innerHTML = `<span class="font-medium">${formatLabel(key)}:</span> ${value}`;
                        additionalInfoElement.appendChild(infoItem);
                    }
                }
                
                additionalInfoContainer.style.display = hasInfo ? 'block' : 'none';
            } else {
                additionalInfoContainer.style.display = 'none';
            }
        } catch (e) {
            additionalInfoContainer.style.display = 'none';
        }
        
        document.getElementById('viewModal').classList.remove('hidden');
    }
    
    function formatLabel(key) {
        return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }
    
    function formatTime(timeString) {
        if (!timeString) return '';
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(hours);
        date.setMinutes(minutes);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    function openApproveModal(bookingId) {
        document.getElementById('approve-booking-id').value = bookingId;
        document.getElementById('approveModal').classList.remove('hidden');
    }
    
    function openRejectModal(bookingId) {
        document.getElementById('reject-booking-id').value = bookingId;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    
    function openCancelModal(bookingId) {
        document.getElementById('cancel-booking-id').value = bookingId;
        document.getElementById('cancelModal').classList.remove('hidden');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
