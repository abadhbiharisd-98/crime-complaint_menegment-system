<link rel="stylesheet" href="admin/assets/wysiwyg/css/froala_editor.css">
  <link rel="stylesheet" href="admin/assets/wysiwyg/css/froala_style.css">
<?php 
include_once 'admin/db_connect.php'; 
?>
<style>
    #cat-list li{
        cursor: pointer;
    }
       #cat-list li:hover {
        color: white;
        background: #007bff8f;
    }
    .prod-item p{
        margin: unset;
    }
    .bid-tag {
    position: absolute;
    right: .5em;
}
</style>
<?php 
$cid = isset($_GET['category_id']) ? $_GET['category_id'] : 0;
?>
<div class="container">
    <div class="col-lg-12">
      <div class="fr-wrapper">
          <?php echo isset($_SESSION['system']['about_content']) && !empty($_SESSION['system']['about_content']) ? html_entity_decode($_SESSION['system']['about_content']) : '<p class="text-center py-4">Welcome to the Crime/Complaints Reporting Management System. Report incidents, track complaints, and follow up with responder teams securely.</p>' ?>
      </div>
    </div>
</div>
