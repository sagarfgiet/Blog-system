<?php
require_once 'includes/functions.php';

$page_title = 'Home - Latest Posts';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Get total posts count
    $stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $per_page);
    
    // Get posts with author information using JOIN
    $sql = "SELECT p.*, u.username as author_name 
            FROM posts p 
            JOIN users u ON p.author_id = u.id 
            WHERE p.status = 'published' 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $posts = $stmt->fetchAll();
    
    // Get categories for each post
    foreach ($posts as &$post) {
        $cat_stmt = $db->prepare("
            SELECT c.name, c.slug 
            FROM categories c 
            JOIN post_categories pc ON c.id = pc.category_id 
            WHERE pc.post_id = :post_id
        ");
        $cat_stmt->execute([':post_id' => $post['id']]);
        $post['categories'] = $cat_stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Error fetching posts: " . $e->getMessage());
    $posts = [];
    $total_pages = 0;
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <h2 class="mb-4">Latest Blog Posts</h2>
        
        <?php if (empty($posts)): ?>
            <div class="alert alert-info">
                No posts found. Be the first to <a href="create-post.php">create a post</a>!
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="card post-card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">
                            <a href="view-post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        
                        <div class="text-muted mb-3">
                            <small>
                                By <?php echo htmlspecialchars($post['author_name']); ?> 
                                on <?php echo formatDate($post['created_at']); ?>
                                
                                <?php if (!empty($post['categories'])): ?>
                                    | 
                                    <?php foreach ($post['categories'] as $index => $category): ?>
                                        <a href="category.php?slug=<?php echo $category['slug']; ?>" 
                                           class="badge bg-secondary text-decoration-none">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                        <?php if ($index < count($post['categories']) - 1) echo ' '; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="card-text post-content">
                            <?php 
                            // Show excerpt of content
                            $content = strip_tags($post['content']);
                            echo strlen($content) > 200 ? substr($content, 0, 200) . '...' : $content;
                            ?>
                        </div>
                        
                        <a href="view-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary mt-3">
                            Read More →
                        </a>
                        
                        <?php if (isLoggedIn() && (isAdmin() || $_SESSION['user_id'] == $post['author_id'])): ?>
                            <div class="mt-3">
                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete-post.php?id=<?php echo $post['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this post?')">
                                    Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Sidebar -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Categories</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <?php foreach (getAllCategories() as $category): ?>
                        <li class="mb-2">
                            <a href="category.php?slug=<?php echo $category['slug']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="create-post.php" class="btn btn-success w-100 mb-2">Create New Post</a>
                    <a href="my-posts.php" class="btn btn-outline-secondary w-100">My Posts</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>