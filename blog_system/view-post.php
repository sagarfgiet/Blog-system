<?php
require_once 'includes/functions.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    $_SESSION['flash_message'] = 'Invalid post ID';
    $_SESSION['flash_class'] = 'alert-danger';
    redirect('index.php');
}

try {
    $db = getDB();
    
    // Get post details with author info
    $sql = "SELECT p.*, u.username as author_name, u.email as author_email 
            FROM posts p 
            JOIN users u ON p.author_id = u.id 
            WHERE p.id = :id AND (p.status = 'published' OR :is_admin = 1 OR p.author_id = :user_id)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':id' => $post_id,
        ':is_admin' => isAdmin() ? 1 : 0,
        ':user_id' => getCurrentUserId() ?? 0
    ]);
    
    $post = $stmt->fetch();
    
    if (!$post) {
        $_SESSION['flash_message'] = 'Post not found or you don\'t have permission to view it';
        $_SESSION['flash_class'] = 'alert-danger';
        redirect('index.php');
    }
    
    $page_title = htmlspecialchars($post['title']);
    
    // Get categories for this post
    $cat_stmt = $db->prepare("
        SELECT c.id, c.name, c.slug 
        FROM categories c 
        JOIN post_categories pc ON c.id = pc.category_id 
        WHERE pc.post_id = :post_id
    ");
    $cat_stmt->execute([':post_id' => $post_id]);
    $post['categories'] = $cat_stmt->fetchAll();
    
    // Get comments for this post
    $comment_stmt = $db->prepare("
        SELECT c.*, u.username as commenter_name 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = :post_id AND c.is_approved = 1 
        ORDER BY c.created_at DESC
    ");
    $comment_stmt->execute([':post_id' => $post_id]);
    $comments = $comment_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error viewing post: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Error loading post';
    $_SESSION['flash_class'] = 'alert-danger';
    redirect('index.php');
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <article class="blog-post">
            <!-- Post header -->
            <header class="mb-4">
                <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <div class="text-muted mb-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <span class="text-white fw-bold">
                                    <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div>
                                <strong><?php echo htmlspecialchars($post['author_name']); ?></strong>
                                <span class="mx-2">•</span>
                                <time datetime="<?php echo $post['created_at']; ?>">
                                    <?php echo formatDate($post['created_at']); ?>
                                </time>
                                <?php if ($post['updated_at'] != $post['created_at']): ?>
                                    <span class="mx-2">•</span>
                                    <small>Updated <?php echo formatDate($post['updated_at']); ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($post['categories'])): ?>
                                <div class="mt-1">
                                    <?php foreach ($post['categories'] as $category): ?>
                                        <a href="category.php?slug=<?php echo $category['slug']; ?>" 
                                           class="badge bg-primary text-decoration-none me-1">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isLoggedIn() && (isAdmin() || $_SESSION['user_id'] == $post['author_id'])): ?>
                            <div class="flex-shrink-0">
                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete-post.php?id=<?php echo $post['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this post?')">
                                    Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($post['status'] === 'draft'): ?>
                    <div class="alert alert-warning">
                        ⚠️ This post is currently in <strong>draft</strong> mode and not visible to the public.
                    </div>
                <?php endif; ?>
            </header>
            
            <!-- Post content -->
            <div class="post-content mb-5">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <!-- Post actions -->
            <div class="border-top pt-4 mb-5">
                <div class="d-flex justify-content-between">
                    <div>
                        <?php if (isLoggedIn()): ?>
                            <button class="btn btn-outline-primary me-2" onclick="sharePost()">📤 Share</button>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-secondary">← Back to Posts</a>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <section class="comments-section">
                <h3 class="mb-4">Comments (<?php echo count($comments); ?>)</h3>
                
                <!-- Add Comment Form -->
                <?php if (isLoggedIn()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Add a Comment</h5>
                            <form method="POST" action="add-comment.php">
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" name="comment" rows="3" 
                                              placeholder="Write your comment here..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Comment</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please <a href="login.php">login</a> to leave a comment.
                    </div>
                <?php endif; ?>
                
                <!-- Comments List -->
                <?php if (empty($comments)): ?>
                    <div class="alert alert-light">
                        No comments yet. Be the first to comment!
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <span class="text-white fw-bold">
                                                <?php 
                                                $initials = $comment['commenter_name'] 
                                                    ? strtoupper(substr($comment['commenter_name'], 0, 1))
                                                    : strtoupper(substr($comment['name'], 0, 1));
                                                echo $initials;
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($comment['commenter_name'] ?? $comment['name']); ?>
                                            </h6>
                                            <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </article>
    </div>
</div>

<script>
function sharePost() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($post['title']); ?>',
            text: 'Check out this blog post!',
            url: window.location.href
        });
    } else {
        // Fallback: Copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>