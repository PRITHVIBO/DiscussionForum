<?php
require_once 'includes/auth_check.php';

$page_title = 'Dashboard';
$additional_css = [];

// Get some basic stats for the dashboard
try {
    $pdo = getDBConnection();
    
    // Get total users count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE is_active = 1");
    $stmt->execute();
    $total_users = $stmt->fetch()['total_users'];
    
    // Get total discussions count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_discussions FROM discussions");
    $stmt->execute();
    $total_discussions = $stmt->fetch()['total_discussions'];
    
    // Get total categories count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_categories FROM categories WHERE is_active = 1");
    $stmt->execute();
    $total_categories = $stmt->fetch()['total_categories'];
    
    // Get recent discussions
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.created_at, d.views, u.name as author_name, c.name as category_name
        FROM discussions d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_discussions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard data fetch error: " . $e->getMessage());
    $total_users = 0;
    $total_discussions = 0;
    $total_categories = 0;
    $recent_discussions = [];
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Welcome back, <?php echo htmlspecialchars($current_user['name']); ?>!</h1>
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        <a href="/home.php" class="btn btn-primary">
                            <i class="fas fa-comments"></i> Go to Forum
                        </a>
                        <a href="#" class="btn btn-success">
                            <i class="fas fa-plus"></i> Create Post
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_users); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Discussions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_discussions); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Categories
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_categories); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Your Posts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="/home.php" class="btn btn-outline-primary btn-block w-100">
                                <i class="fas fa-home"></i> Forum Home
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="/categories.php" class="btn btn-outline-success btn-block w-100">
                                <i class="fas fa-tags"></i> Browse Categories
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="/popular.php" class="btn btn-outline-warning btn-block w-100">
                                <i class="fas fa-fire"></i> Popular Posts
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="#" class="btn btn-outline-info btn-block w-100">
                                <i class="fas fa-plus"></i> Create Discussion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Profile Summary -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Your Profile</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-auto">
                            <div class="avatar-circle">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="mb-1"><?php echo htmlspecialchars($current_user['name']); ?></h5>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($current_user['email']); ?></p>
                            <p class="text-muted mb-0">
                                <small>Member since <?php echo date('F j, Y', strtotime($current_user['created_at'])); ?></small>
                            </p>
                            <?php if ($current_user['last_login']): ?>
                                <p class="text-muted mb-0">
                                    <small>Last login: <?php echo date('M j, Y g:i A', strtotime($current_user['last_login'])); ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="#" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                        <a href="#" class="btn btn-sm btn-secondary">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Discussions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Discussions</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_discussions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-gray-300 mb-3"></i>
                            <h5 class="text-gray-500">No discussions yet</h5>
                            <p class="text-gray-400">Be the first to start a discussion!</p>
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Discussion
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Discussion</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>Views</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_discussions as $discussion): ?>
                                        <tr>
                                            <td>
                                                <a href="#" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($discussion['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($discussion['author_name'] ?? 'Unknown'); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($discussion['category_name'] ?? 'General'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($discussion['views']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($discussion['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center">
                            <a href="/home.php" class="btn btn-primary">
                                View All Discussions
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border: 1px solid #e3e6f0;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e;
}

.text-xs {
    font-size: 0.7rem;
}

.avatar-circle {
    width: 60px;
    height: 60px;
    background-color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.text-gray-300 {
    color: #dddfeb;
}

.text-gray-400 {
    color: #b7b9cc;
}

.text-gray-500 {
    color: #858796;
}

.text-gray-800 {
    color: #5a5c69;
}
</style>

<?php include 'includes/footer.php'; ?>