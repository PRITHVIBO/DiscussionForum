<?php
require_once 'includes/auth_check.php';

$page_title = 'Popular Discussions';
$additional_css = [];

// Get popular discussions based on views and recent activity
try {
    $pdo = getDBConnection();
    
    // Get popular discussions (sorted by views and recent activity)
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.content, d.created_at, d.views, d.is_pinned, d.is_locked,
               u.name as author_name, c.name as category_name, c.id as category_id,
               COUNT(replies.id) as reply_count
        FROM discussions d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        LEFT JOIN discussions replies ON replies.id = d.id  -- This would be replies table in a full implementation
        WHERE d.views > 0
        GROUP BY d.id, d.title, d.content, d.created_at, d.views, d.is_pinned, d.is_locked, u.name, c.name, c.id
        ORDER BY 
            CASE 
                WHEN d.views >= 100 THEN 3
                WHEN d.views >= 50 THEN 2
                WHEN d.views >= 10 THEN 1
                ELSE 0
            END DESC,
            d.views DESC, 
            d.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $popular_discussions = $stmt->fetchAll();
    
    // Get trending topics (discussions with high activity in the last 7 days)
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.views, d.created_at,
               u.name as author_name, c.name as category_name
        FROM discussions d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY d.views DESC, d.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $trending_discussions = $stmt->fetchAll();
    
    // Get most viewed discussions of all time
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.views, d.created_at,
               u.name as author_name, c.name as category_name
        FROM discussions d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        ORDER BY d.views DESC
        LIMIT 10
    ");
    $stmt->execute();
    $most_viewed = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Popular page error: " . $e->getMessage());
    $popular_discussions = [];
    $trending_discussions = [];
    $most_viewed = [];
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2">
                        <i class="fas fa-fire text-danger me-2"></i>
                        Popular Discussions
                    </h1>
                    <p class="text-muted">Discover the most popular and trending discussions in our community</p>
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

    <div class="row">
        <!-- Main Popular Discussions -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Popular Discussions
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($popular_discussions)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-fire fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No popular discussions yet</h4>
                            <p class="text-muted">Start engaging with discussions to see popular ones here!</p>
                            <a href="/home.php" class="btn btn-primary">
                                <i class="fas fa-comments"></i> Browse All Discussions
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="popular-discussions-list">
                            <?php foreach ($popular_discussions as $index => $discussion): ?>
                                <div class="popular-discussion-item <?php echo $index < count($popular_discussions) - 1 ? 'border-bottom' : ''; ?> pb-3 mb-3">
                                    <div class="row align-items-start">
                                        <div class="col-auto">
                                            <div class="popularity-rank">
                                                <?php if ($index < 3): ?>
                                                    <span class="badge bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark'); ?> rank-badge">
                                                        #<?php echo $index + 1; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark rank-badge">
                                                        #<?php echo $index + 1; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h6 class="discussion-title mb-2">
                                                <a href="#" class="text-decoration-none">
                                                    <?php if ($discussion['is_pinned']): ?>
                                                        <i class="fas fa-thumbtack text-warning me-1" title="Pinned"></i>
                                                    <?php endif; ?>
                                                    <?php if ($discussion['is_locked']): ?>
                                                        <i class="fas fa-lock text-danger me-1" title="Locked"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($discussion['title']); ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted small mb-2">
                                                <?php echo htmlspecialchars(substr(strip_tags($discussion['content']), 0, 100)) . '...'; ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="discussion-meta">
                                                    <span class="badge bg-secondary me-2">
                                                        <?php echo htmlspecialchars($discussion['category_name'] ?? 'General'); ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        by <strong><?php echo htmlspecialchars($discussion['author_name'] ?? 'Unknown'); ?></strong>
                                                        • <?php echo date('M j, Y', strtotime($discussion['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="discussion-stats">
                                                    <span class="badge bg-danger me-2">
                                                        <i class="fas fa-eye"></i> <?php echo number_format($discussion['views']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Trending This Week -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-trending-up me-2"></i>
                        Trending This Week
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($trending_discussions)): ?>
                        <p class="text-muted small">No trending discussions this week.</p>
                    <?php else: ?>
                        <div class="trending-list">
                            <?php foreach (array_slice($trending_discussions, 0, 5) as $discussion): ?>
                                <div class="trending-item mb-3">
                                    <h6 class="small mb-1">
                                        <a href="#" class="text-decoration-none">
                                            <?php echo htmlspecialchars($discussion['title']); ?>
                                        </a>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($discussion['author_name'] ?? 'Unknown'); ?>
                                        </small>
                                        <span class="badge bg-success small">
                                            <?php echo number_format($discussion['views']); ?> views
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Most Viewed All Time -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-crown me-2"></i>
                        Most Viewed All Time
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($most_viewed)): ?>
                        <p class="text-muted small">No discussions available yet.</p>
                    <?php else: ?>
                        <div class="most-viewed-list">
                            <?php foreach (array_slice($most_viewed, 0, 5) as $index => $discussion): ?>
                                <div class="most-viewed-item mb-3 d-flex align-items-start">
                                    <div class="me-2">
                                        <span class="badge bg-<?php echo $index == 0 ? 'warning' : 'light text-dark'; ?> small">
                                            <?php echo $index + 1; ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">
                                            <a href="#" class="text-decoration-none">
                                                <?php echo htmlspecialchars($discussion['title']); ?>
                                            </a>
                                        </h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($discussion['author_name'] ?? 'Unknown'); ?>
                                            </small>
                                            <span class="badge bg-info small">
                                                <?php echo number_format($discussion['views']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-dark text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Community Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="stat-row mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Popular Posts</span>
                            <span class="fw-bold"><?php echo count($popular_discussions); ?></span>
                        </div>
                    </div>
                    <div class="stat-row mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Trending This Week</span>
                            <span class="fw-bold"><?php echo count($trending_discussions); ?></span>
                        </div>
                    </div>
                    <div class="stat-row">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Views</span>
                            <span class="fw-bold">
                                <?php echo number_format(array_sum(array_column($most_viewed, 'views'))); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary-color), #6f42c1);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #1cc88a, #17a673);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #36b9cc, #258391);
}

.bg-gradient-dark {
    background: linear-gradient(135deg, #5a5c69, #3d3e47);
}

.rank-badge {
    font-size: 0.9rem;
    padding: 0.5em 0.7em;
    font-weight: 600;
}

.popularity-rank {
    margin-top: 0.25rem;
}

.popular-discussion-item {
    transition: background-color 0.2s ease-in-out;
    border-radius: 0.5rem;
    padding: 0.5rem;
    margin: -0.5rem;
}

.popular-discussion-item:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.discussion-title a {
    color: #2c3e50;
    font-weight: 600;
}

.discussion-title a:hover {
    color: var(--primary-color);
}

.trending-item, .most-viewed-item {
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s ease-in-out;
}

.trending-item:hover, .most-viewed-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.stat-row {
    padding: 0.25rem 0;
    border-bottom: 1px solid #e3e6f0;
}

.stat-row:last-child {
    border-bottom: none;
}

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    border-bottom: none;
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