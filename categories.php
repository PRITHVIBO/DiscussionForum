<?php
require_once 'includes/auth_check.php';

$page_title = 'Categories';
$additional_css = [];

// Get all categories with discussion counts
try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.description, c.created_at,
               COUNT(d.id) as discussion_count,
               MAX(d.created_at) as last_activity
        FROM categories c
        LEFT JOIN discussions d ON c.id = d.category_id
        WHERE c.is_active = 1
        GROUP BY c.id, c.name, c.description, c.created_at
        ORDER BY c.name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // If no categories exist, create some default ones
    if (empty($categories)) {
        $default_categories = [
            ['name' => 'General Discussion', 'description' => 'General topics and conversations'],
            ['name' => 'Technology', 'description' => 'Technology news, tips, and discussions'],
            ['name' => 'Programming', 'description' => 'Programming languages, frameworks, and development'],
            ['name' => 'Web Development', 'description' => 'Frontend, backend, and full-stack development'],
            ['name' => 'Mobile Development', 'description' => 'iOS, Android, and mobile app development'],
            ['name' => 'Announcements', 'description' => 'Important announcements and updates'],
        ];
        
        foreach ($default_categories as $category) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$category['name'], $category['description']]);
        }
        
        // Refetch categories
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.description, c.created_at,
                   COUNT(d.id) as discussion_count,
                   MAX(d.created_at) as last_activity
            FROM categories c
            LEFT JOIN discussions d ON c.id = d.category_id
            WHERE c.is_active = 1
            GROUP BY c.id, c.name, c.description, c.created_at
            ORDER BY c.name ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Categories page error: " . $e->getMessage());
    $categories = [];
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2">Discussion Categories</h1>
                    <p class="text-muted">Explore different topics and find discussions that interest you</p>
                </div>
                <div class="btn-toolbar" role="toolbar">
                    <a href="/home.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Forum
                    </a>
                    <a href="#" class="btn btn-success">
                        <i class="fas fa-plus"></i> New Discussion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="row">
        <?php if (empty($categories)): ?>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No categories available</h4>
                        <p class="text-muted">Categories will appear here once they are created.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                    <div class="card category-card shadow-sm h-100">
                        <div class="card-header bg-transparent border-bottom-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-folder text-primary me-2"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h5>
                                <span class="badge bg-secondary">
                                    <?php echo number_format($category['discussion_count']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                            
                            <div class="category-stats mt-3">
                                <small class="text-muted d-block">
                                    <i class="fas fa-comments me-1"></i>
                                    <?php echo number_format($category['discussion_count']); ?> 
                                    discussion<?php echo $category['discussion_count'] != 1 ? 's' : ''; ?>
                                </small>
                                
                                <?php if ($category['last_activity']): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-clock me-1"></i>
                                        Last activity: <?php echo date('M j, Y', strtotime($category['last_activity'])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-clock me-1"></i>
                                        No discussions yet
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between">
                                <a href="#" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Discussions
                                </a>
                                <a href="#" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Start Discussion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Category Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-item">
                                <h3 class="text-primary"><?php echo count($categories); ?></h3>
                                <p class="text-muted mb-0">Total Categories</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-item">
                                <h3 class="text-success">
                                    <?php echo array_sum(array_column($categories, 'discussion_count')); ?>
                                </h3>
                                <p class="text-muted mb-0">Total Discussions</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-item">
                                <?php 
                                $active_categories = array_filter($categories, function($cat) { 
                                    return $cat['discussion_count'] > 0; 
                                }); 
                                ?>
                                <h3 class="text-info"><?php echo count($active_categories); ?></h3>
                                <p class="text-muted mb-0">Active Categories</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-item">
                                <?php 
                                $most_active = !empty($categories) ? max(array_column($categories, 'discussion_count')) : 0;
                                ?>
                                <h3 class="text-warning"><?php echo $most_active; ?></h3>
                                <p class="text-muted mb-0">Most Active Category</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.category-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border: 1px solid #e3e6f0;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.category-stats {
    border-top: 1px solid #e3e6f0;
    padding-top: 1rem;
}

.stat-item {
    padding: 1rem;
    border-left: 3px solid transparent;
    transition: border-left-color 0.2s ease-in-out;
}

.stat-item:hover {
    border-left-color: var(--primary-color);
    background-color: rgba(78, 115, 223, 0.05);
}

.card-header {
    background: linear-gradient(135deg, rgba(78, 115, 223, 0.1), rgba(111, 66, 193, 0.1));
}

.badge {
    font-size: 0.8rem;
    padding: 0.4em 0.6em;
}

.btn-sm {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
}

.categories-grid {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-toolbar {
        justify-content: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>