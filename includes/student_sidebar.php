<?php
// Don't start a session here since it's already started in the main pages
?>
<div class="bg-gray-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out" id="sidebar">
    <div class="flex items-center space-x-2 px-4">
        <i class="fas fa-school text-emerald-400"></i>
        <div>
            <span class="text-xl font-bold">CHMSU BAO</span>
            <p class="text-xs text-gray-400">Student Portal</p>
        </div>
    </div>
    <nav>
        <a href="dashboard.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
        <a href="request.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">
            <i class="fas fa-clipboard-list mr-2"></i>My Requests
        </a>
        <a href="inventory.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">
            <i class="fas fa-box mr-2"></i>Order Items
        </a>
        <a href="orders.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">
            <i class="fas fa-shopping-cart mr-2"></i>My Orders
        </a>
        <a href="profile.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">
            <i class="fas fa-user mr-2"></i>Profile
        </a>
        <a href="../logout.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700 mt-6">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </nav>
</div>

