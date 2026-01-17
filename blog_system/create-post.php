<?php
require_once 'includes/functions.php';

// Only logged-in users can create posts
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please login to create a post';
    $_SESSION['flash_class'] = 'alert-danger';
    redirect('login.php');
}

$page_title = 'Create New Post';
$errors = [];
$success = false;

// Get all categories
$categories = getAllCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = sanitize($_POST['status'] ?? 'draft');
    $selected_categories = $_POST['categories'] ?? [];
    
    // Validation
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($title) < 5) {
        $errors['title'] = 'Title must be at least 5 characters';
    }
    
    if (empty($content)) {
        $errors['content'] = 'Content is required';
    } elseif (strlen($content) < 50) {
        $errors['content'] = 'Content must be at least 50 characters';
    }
    
    if (!in_array($status, ['published', 'draft'])) {
        $errors['status'] = 'Invalid status';
    }
    
    // Validate categories (if any selected)
    if (!empty($selected_categories)) {
        $valid_categories = array_column($categories, 'id');
        foreach ($selected_categories as $cat_id) {
            if (!in_array($cat_id, $valid_categories)) {
                $errors['categories'] = 'Invalid category selected';
                break;
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Start transaction for data consistency
            $db->beginTransaction();
            
            // Insert post
            $sql = "INSERT INTO posts (title, content, author_id, status) 
                    VALUES (:title, :content, :author_id, :status)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':author_id' => getCurrentUserId(),
                ':status' => $status
            ]);
            
            $post_id = $db->lastInsertId();
            
            // Insert categories if selected
            if (!empty($selected_categories)) {
                $cat_sql = "INSERT INTO post_categories (post_id, category_id) VALUES ";
                $cat_values = [];
                
                foreach ($selected_categories as $cat_id) {
                    $cat_values[] = "($post_id, $cat_id)";
                }
                
                $cat_sql .= implode(', ', $cat_values);
                $db->exec($cat_sql);
            }
            
            // Commit transaction
            $db->commit();
            
            $success = true;
            $_SESSION['flash_message'] = 'Post ' . ($status === 'published' ? 'published' : 'saved as draft') . ' successfully!';
            $_SESSION['flash_class'] = 'alert-success';
            
            redirect('view-post.php?id=' . $post_id);
            
        } catch (PDOException $e) {
            // Rollback on error
            $db->rollBack();
            error_log("Create Post Error: " . $e->getMessage());
            $errors['general'] = 'Failed to create post. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Create New Blog Post</h4>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="title" class="form-label">Post Title</label>
                        <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                               id="title" name="title" value="<?php echo $_POST['title'] ?? ''; ?>" 
                               placeholder="Enter post title" required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                        <?php endif; ?>
                        <div class="form-text">Make it catchy and descriptive!</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Post Content</label>
                        <textarea class="form-control <?php echo isset($errors['content']) ? 'is-invalid' : ''; ?>" 
                                  id="content" name="content" rows="10" 
                                  placeholder="Write your post content here..." required><?php echo $_POST['content'] ?? ''; ?></textarea>
                        <?php if (isset($errors['content'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['content']; ?></div>
                        <?php endif; ?>
                        <div class="form-text">Minimum 50 characters. You can use HTML for formatting.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categories</label>
                        <div class="row">
                            <?php foreach ($categories as $category): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="categories[]" 
                                               value="<?php echo $category['id']; ?>" 
                                               id="cat_<?php echo $category['id']; ?>"
                                               <?php echo (isset($_POST['categories']) && in_array($category['id'], $_POST['categories'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cat_<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors['categories'])): ?>
                            <div class="text-danger"><?php echo $errors['categories']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Post Status</label>
                        <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" 
                                id="status" name="status">
                            <option value="draft" <?php echo ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publish Now</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>