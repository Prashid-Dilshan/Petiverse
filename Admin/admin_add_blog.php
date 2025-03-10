

<?php
include('../db.php');
include('session_check.php');

// Admin user information
$admin_user_id = 1; 
$admin_user_name = "admin";

// Handle Add, Edit, and Delete Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Blog
    if (isset($_POST['add_blog'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category = $_POST['category'];
        $photo = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        }

        $status = "approved"; 
        $stmt = $conn->prepare("INSERT INTO user_blogs (user_id, user_name, title, content, category, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $admin_user_id, $admin_user_name, $title, $content, $category, $photo, $status);
        $stmt->execute();

        header("Location: admin_add_blog.php");
        exit();
    }

    // Edit Blog
    if (isset($_POST['edit_blog'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category = $_POST['category'];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
            $stmt = $conn->prepare("UPDATE user_blogs SET title = ?, content = ?, category = ?, photo = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $title, $content, $category, $photo, $id);
        } else {
            $stmt = $conn->prepare("UPDATE user_blogs SET title = ?, content = ?, category = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $content, $category, $id);
        }

        $stmt->execute();
        header("Location: admin_add_blog.php");
        exit();
    }

    // Delete Blog
    if (isset($_POST['delete_blog'])) {
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM user_blogs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: admin_add_blog.php");
        exit();
    }
}

// Retrieve Blogs
$stmt = $conn->prepare("SELECT * FROM user_blogs WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $admin_user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Petiverse - Blog Management</title>
  <link rel="stylesheet" href="../Admin/admin_sidebar.css">
  <style>
 
/* Reset styles */
body, h1, h2, h3, p, ul, li, a, button, input, textarea, select, form {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    font-size: 16px;
    line-height: 1.6;
    background-color: #f9f9f9;
    color: #333;
}



/* Main Content */
main {
    margin-left: 22%;
    padding: 20px;
}

/* Form Styling */
.form-container {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

form label {
    display: block;
    margin-bottom: 10px;
    font-weight: bold;
}

form input[type="text"], 
form textarea, 
form select, 
form input[type="file"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

form button {
    background-color:#333 ;
    color: white;
padding: 10px 15px;
border: none;
border-radius: 4px;
cursor: pointer;
font-size: 16px;
width: 15%;
margin-bottom: 15px;
text-decoration: none;
display: block;
}

form button:hover {
    background-color:#555 ;
    color: white;
padding: 10px 15px;
border: none;
border-radius: 4px;
cursor: pointer;
font-size: 16px;
width: 15%;
margin-bottom: 15px;
text-decoration: none;
display: block;
}



form .delbtn{
    background-color: #ef4444;
color: white;
padding: 10px 15px;
border: none;
border-radius: 4px;
cursor: pointer;
font-size: 16px;
width: 15%;
margin-bottom: 15px;
text-decoration: none;
display: block;
}


form .delbtn:hover{
    background-color: #dc2626;
color: white;
padding: 10px 15px;
border: none;
border-radius: 4px;
cursor: pointer;
font-size: 16px;
width: 15%;
margin-bottom: 15px;
text-decoration: none;
display: block;
}



/* Blog Display */
.blog-container {
    margin: 20px 0;
}

.blog {
    background: white;
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.blog h3 {
    color: #004d99;
    margin-bottom: 10px;
}

.blog img {
    max-width: 100%;
    height: auto;
    margin: 10px 0;
    border-radius: 8px;
}

.blog form button {
    margin-right: 10px;
}

/* Edit Form Popup */
#editForm {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    z-index: 1000;
}

.editing::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 999;
}







/* Button for canceling */
#editForm button[type="button"] {
    background-color: #ccc;
    color: #333;
}

#editForm button[type="button"]:hover {
    background-color: #aaa;
}

/* Responsive Design */
@media (max-width: 768px) {
    nav ul {
        width: 100%;
        height: auto;
        position: static;
    }

    main {
        margin-left: 0;
    }
}


</style>


</head>
<body>
<header>
    <h1>Create Blog</h1>
</header>

<nav>
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="user_management.php">User Management</a></li>
        <li><a href="shop_management.php">Shop Management</a></li>
        <li><a href="community_controls.php">Community Controls</a></li>
        <li><a href="blog_management.php">Blog Management</a></li>
        <li><a href="admin_daycare_management.php">Daycare Management</a></li>
        <li><a href="lost_found_pets.php">Lost & Found Pets</a></li>
        <li><a href="special_events.php">Special Events</a></li>
        <li><a href="vet_management.php">Vet Management</a></li>
        <li><a href="moderator_management.php">Moderator Management</a></li>
        <li><a href="petselling.php">Pet selling</a><li>        
        <li><a href="view_feedback.php">Feedbacks</a></li>
        <li><a href="logout.php" onclick="return confirmLogout();">Logout</a></li>
    </ul>
</nav>
<main>
<h1>Admin Blog Management</h1>

<!-- Add Blog Form -->
<div class="form-container">
    <h2>Add Blog</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Title:</label>
        <input type="text" name="title" required>
        <label>Content:</label>
        <textarea name="content" required></textarea>
        <label>Category:</label>
        <select name="category" required>
            <option value="Health">Health</option>
            <option value="Entertainment">Entertainment</option>
            <option value="Events">Events</option>
            <option value="Wisdom">Wisdom</option>
            <option value="Experience">Experience</option>
        </select>
        <label>Photo:</label>
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="add_blog">Add Blog</button>
    </form>
</div>

<!-- Display Blogs -->
<div class="blog-container">
    <h2>Existing Blogs</h2>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="blog">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?></p>
            <?php if (!empty($row['photo'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($row['photo']); ?>" alt="Blog Image">
            <?php endif; ?>
            <form method="POST" style="margin-top: 10px;">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                <button type="button" class="editbtn"onclick="showEditForm(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['title']); ?>', '<?php echo htmlspecialchars($row['content']); ?>', '<?php echo htmlspecialchars($row['category']); ?>')">Edit</button>
                <button type="submit" name="delete_blog" class="delbtn">Delete</button>
            </form>
        </div>
    <?php endwhile; ?>
</div>

<!-- Edit Blog Form (Popup) -->
<div id="editForm" style="display: none;" class="form-container">
    <h2>Edit Blog</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="edit-id">
        <label>Title:</label>
        <input type="text" name="title" id="edit-title" required>
        <label>Content:</label>
        <textarea name="content" id="edit-content" required></textarea>
        <label>Category:</label>
        <select name="category" id="edit-category" required>
            <option value="Health">Health</option>
            <option value="Entertainment">Entertainment</option>
            <option value="Events">Events</option>
            <option value="Wisdom">Wisdom</option>
            <option value="Experience">Experience</option>
        </select>
        <label>Photo (Optional):</label>
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="edit_blog">Update Blog</button>
        <button type="button" onclick="hideEditForm()">Cancel</button>
    </form>
</div>

<script>
    function showEditForm(id, title, content, category) {
        document.getElementById('editForm').style.display = 'block';
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-title').value = title;
        document.getElementById('edit-content').value = content;
        document.getElementById('edit-category').value = category;
        document.body.classList.add('editing'); // Add overlay
    }

    function hideEditForm() {
        document.getElementById('editForm').style.display = 'none';
        document.body.classList.remove('editing'); 
    }
</script>

</main>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>

















