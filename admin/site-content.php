<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Site Content Management - T BUS";

$database = new Database();
$db = $database->getConnection();

// Handle content updates
if ($_POST) {
    try {
        $page = sanitizeInput($_POST['page']);
        $section = sanitizeInput($_POST['section']);
        $content_title = sanitizeInput($_POST['content_title']);
        $content_text = $_POST['content_text'];
        $content_html = $_POST['content_html'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if content exists
        $checkQuery = "SELECT id FROM site_content WHERE page = ? AND section = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$page, $section]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing content
            $query = "UPDATE site_content SET content_title = ?, content_text = ?, content_html = ?, 
                     is_active = ?, updated_at = NOW() WHERE page = ? AND section = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$content_title, $content_text, $content_html, $is_active, $page, $section]);
        } else {
            // Create new content
            $query = "INSERT INTO site_content (page, section, content_title, content_text, content_html, is_active) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$page, $section, $content_title, $content_text, $content_html, $is_active]);
        }
        
        $_SESSION['success'] = "Content updated successfully";
        redirect('site-content.php?page=' . $page);
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'homepage';
$pages = ['homepage', 'about', 'contact', 'faq'];

// Get content for current page
$contentQuery = "SELECT * FROM site_content WHERE page = ? ORDER BY display_order, section";
$contentStmt = $db->prepare($contentQuery);
$contentStmt->execute([$current_page]);
$page_content = $contentStmt->fetchAll(PDO::FETCH_ASSOC);

// Group content by section
$content_by_section = [];
foreach ($page_content as $content) {
    $content_by_section[$content['section']] = $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Site Content Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../public/index.php" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-eye"></i> View Site
                        </a>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Navigation -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Select Page to Edit</h6>
                        <ul class="nav nav-pills">
                            <?php foreach ($pages as $page): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == $page ? 'active' : ''; ?>" 
                                   href="?page=<?php echo $page; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $page)); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Content Sections -->
                <div class="row">
                    <div class="col-lg-8">
                        <?php 
                        // Define sections for each page
                        $page_sections = [
                            'homepage' => ['banner', 'how_it_works', 'features', 'testimonials'],
                            'about' => ['main', 'mission', 'team', 'contact_info'],
                            'contact' => ['main', 'form', 'map'],
                            'faq' => ['main', 'general', 'booking', 'payment']
                        ];
                        
                        $sections = isset($page_sections[$current_page]) ? $page_sections[$current_page] : ['main'];
                        
                        foreach ($sections as $section): 
                            $content = isset($content_by_section[$section]) ? $content_by_section[$section] : null;
                        ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $section)); ?> Section
                                    <?php if ($content && $content['is_active']): ?>
                                        <span class="badge bg-success ms-2">Active</span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                                    <input type="hidden" name="section" value="<?php echo $section; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Section Title</label>
                                        <input type="text" class="form-control" name="content_title" 
                                               value="<?php echo $content ? htmlspecialchars($content['content_title']) : ''; ?>"
                                               placeholder="Enter section title">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Content Text (Plain Text)</label>
                                        <textarea class="form-control" name="content_text" rows="4"
                                                  placeholder="Enter plain text content"><?php echo $content ? htmlspecialchars($content['content_text']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Content HTML (Rich Text)</label>
                                        <textarea class="form-control summernote" name="content_html" rows="6"
                                                  placeholder="Enter HTML content"><?php echo $content ? htmlspecialchars($content['content_html']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" name="is_active" 
                                               id="active-<?php echo $section; ?>" 
                                               <?php echo (!$content || $content['is_active']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="active-<?php echo $section; ?>">
                                            Show this section on the website
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Preview Panel -->
                    <div class="col-lg-4">
                        <div class="card sticky-top" style="top: 100px;">
                            <div class="card-header">
                                <h6 class="mb-0">Live Preview</h6>
                            </div>
                            <div class="card-body">
                                <div class="preview-area border rounded p-3 bg-light" id="previewArea" style="min-height: 200px;">
                                    <p class="text-muted mb-0">Content preview will appear here</p>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="updatePreview()">
                                        <i class="fas fa-sync"></i> Update Preview
                                    </button>
                                </div>
                                
                                <div class="mt-3">
                                    <h6>Quick Tips</h6>
                                    <ul class="small text-muted">
                                        <li>Use plain text for simple content</li>
                                        <li>Use HTML editor for rich formatting</li>
                                        <li>You can use both fields together</li>
                                        <li>Uncheck "Show this section" to hide content</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        // Initialize Summernote editor
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        });

        // Update preview
        function updatePreview() {
            const previewArea = document.getElementById('previewArea');
            const activeForm = document.querySelector('form');
            
            if (activeForm) {
                const title = activeForm.querySelector('input[name="content_title"]').value;
                const text = activeForm.querySelector('textarea[name="content_text"]').value;
                const html = activeForm.querySelector('textarea[name="content_html"]').value;
                
                let previewHtml = '';
                
                if (title) {
                    previewHtml += `<h4>${title}</h4>`;
                }
                
                if (text) {
                    previewHtml += `<p>${text.replace(/\n/g, '<br>')}</p>`;
                }
                
                if (html) {
                    previewHtml += html;
                }
                
                if (!previewHtml) {
                    previewHtml = '<p class="text-muted">No content to preview</p>';
                }
                
                previewArea.innerHTML = previewHtml;
            }
        }

        // Auto-update preview when typing
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[name="content_title"], textarea[name="content_text"]')) {
                setTimeout(updatePreview, 500);
            }
        });

        // Initialize preview on page load
        setTimeout(updatePreview, 1000);
    </script>
</body>
</html>