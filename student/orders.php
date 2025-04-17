<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "My Orders - CHMSU BAO";
$base_url = "..";

// Get all orders for the current user
$query = "SELECT o.*, i.name as item_name, i.price 
          FROM orders o 
          JOIN inventory i ON o.inventory_id = i.id 
          WHERE o.user_id = ? 
          ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">My Orders</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $_SESSION['user_name']; ?></span>
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
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-gray-900">Order History</h2>
                        <p class="text-gray-500">View and track your orders from the Business Affairs Office</p>
                    </div>
                    <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                        <i class="fas fa-plus mr-2"></i> New Order
                    </a>
                </div>
                
                <!-- Orders table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Your Orders</h3>
                    </div>
                    
                    <?php if ($result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($order = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $order['order_id']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $order['item_name']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $order['quantity']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                                ₱<?php echo number_format($order['total_price'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($order['status'] == 'pending'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Pending
                                                    </span>
                                                <?php elseif ($order['status'] == 'approved'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Ready for Pickup
                                                    </span>
                                                <?php elseif ($order['status'] == 'completed'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Completed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Rejected
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo format_date($order['created_at']); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500 mb-4">You haven't placed any orders yet.</p>
                            <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                                Browse Items
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Order Information -->
                <div class="bg-white rounded-lg shadow mt-6 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Information</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Payment Process</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                After submitting an order, please proceed to the Business Affairs Office cashier for payment. 
                                Your items will be ready for pickup once payment is confirmed.
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Order Status</h4>
                            <ul class="mt-1 text-sm text-gray-600 list-disc list-inside space-y-1">
                                <li><span class="font-medium">Pending</span>: Your order has been submitted and is awaiting approval.</li>
                                <li><span class="font-medium">Ready for Pickup</span>: Your order has been approved and is ready for pickup at the BAO office.</li>
                                <li><span class="font-medium">Completed</span>: You have received your ordered items.</li>
                                <li><span class="font-medium">Rejected</span>: Your order could not be processed. Please contact the BAO office for more information.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Contact Information</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                For any questions regarding your orders, please contact the Business Affairs Office:
                                <br>
                                Email: bao@chmsu.edu.ph
                                <br>
                                Phone: (123) 456-7890
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
</script>

<?php include '../includes/footer.php'; ?>

