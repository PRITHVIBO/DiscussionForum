<?php
require_once 'includes/auth_check.php';

$page_title = 'Forum Home';
$additional_css = [];

// Get discussions with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDBConnection();
    
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM discussions");
    $stmt->execute();
    $total_discussions = $stmt->fetch()['total'];
    $total_pages = ceil($total_discussions / $per_page);
    
    // Get discussions for current page
    $stmt = $pdo->prepare("
        SELECT d.id, d.title, d.content, d.created_at, d.views, d.is_pinned, d.is_locked,
               u.name as author_name, c.name as category_name, c.id as category_id
        FROM discussions d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        ORDER BY d.is_pinned DESC, d.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $discussions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Forum home data fetch error: " . $e->getMessage());
    $discussions = [];
    $total_discussions = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="jumbotron bg-primary text-white rounded p-4">
                <div class="container">
                    <h1 class="display-4">Welcome to the Discussion Forum</h1>
                    <p class="lead">Join the conversation! Share ideas, ask questions, and connect with our community.</p>
                    <hr class="my-4" style="border-color: rgba(255,255,255,0.3);">
                    <p>Most online communities lack a simple, organized, and user-friendly platform where users can engage in topic-based discussions, post questions, and exchange ideas. Our goal is to provide a lightweight, modern, and responsive discussion forum to address this need.</p>
                    <a class="btn btn-light btn-lg" href="#" role="button">
                        <i class="fas fa-plus"></i> Create New Discussion
                    </a>
                    <a class="btn btn-outline-light btn-lg" href="/categories.php" role="button">
                        <i class="fas fa-tags"></i> Browse Categories
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Forum Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Recent Discussions</h2>
                    <p class="text-muted">
                        <?php echo number_format($total_discussions); ?> discussions • Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?>
                    </p>
                </div>
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        <button type="button" class="btn btn-success" onclick="location.href='#'">
                            <i class="fas fa-plus"></i> New Discussion
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-sort"></i> Sort By
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?sort=recent">Most Recent</a></li>
                                <li><a class="dropdown-item" href="?sort=popular">Most Popular</a></li>
                                <li><a class="dropdown-item" href="?sort=views">Most Viewed</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discussions List -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($discussions)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No discussions yet</h4>
                        <p class="text-muted">Be the first to start a conversation in our community!</p>
                        <button type="button" class="btn btn-primary btn-lg" onclick="location.href='#'">
                            <i class="fas fa-plus"></i> Start First Discussion
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="discussions-list">
                    <?php foreach ($discussions as $discussion): ?>
                        <div class="card shadow-sm mb-3 discussion-card">
                            <div class="card-body">
                                <div class="row align-items-start">
                                    <!-- Discussion Icon/Avatar -->
                                    <div class="col-auto">
                                        <div class="discussion-avatar">
                                            <i class="fas fa-comment-alt"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Discussion Content -->
                                    <div class="col">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-1">
                                                <a href="#" class="text-decoration-none discussion-title">
                                                    <?php if ($discussion['is_pinned']): ?>
                                                        <i class="fas fa-thumbtack text-warning me-1" title="Pinned"></i>
                                                    <?php endif; ?>
                                                    <?php if ($discussion['is_locked']): ?>
                                                        <i class="fas fa-lock text-danger me-1" title="Locked"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($discussion['title']); ?>
                                                </a>
                                            </h5>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($discussion['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <p class="card-text text-muted discussion-excerpt">
                                            <?php echo htmlspecialchars(substr(strip_tags($discussion['content']), 0, 150)) . (strlen($discussion['content']) > 150 ? '...' : ''); ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="discussion-meta">
                                                <span class="badge bg-secondary me-2">
                                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($discussion['category_name'] ?? 'General'); ?>
                                                </span>
                                                <small class="text-muted">
                                                    by <strong><?php echo htmlspecialchars($discussion['author_name'] ?? 'Unknown'); ?></strong>
                                                </small>
                                            </div>
                                            <div class="discussion-stats">
                                                <small class="text-muted me-3">
                                                    <i class="fas fa-eye"></i> <?php echo number_format($discussion['views']); ?> views
                                                </small>
                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-arrow-right"></i> Read More
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Discussions pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.jumbotron {
    background: linear-gradient(135deg, var(--primary-color), #6f42c1);
}

.discussion-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border-left: 4px solid transparent;
}

.discussion-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-left-color: var(--primary-color);
}

.discussion-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--primary-color), #6f42c1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.discussion-title {
    color: #2c3e50;
    font-weight: 600;
}

.discussion-title:hover {
    color: var(--primary-color);
}

.discussion-excerpt {
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

.discussion-meta .badge {
    font-size: 0.75rem;
}

.discussion-stats {
    display: flex;
    align-items: center;
}

.btn-outline-primary:hover {
    transform: translateX(2px);
}

.page-link {
    color: var(--primary-color);
}

.page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.discussions-list {
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
</style>

<?php include 'includes/footer.php'; ?>