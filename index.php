<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('admin/db_connect.php');
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $res = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($res && $res instanceof mysqli_result && $res->num_rows > 0) {
        $query = $res->fetch_array();
        if (is_array($query)) {
            foreach ($query as $key => $value) {
                if (!is_numeric($key))
                    $_SESSION['system'][$key] = $value;
            }
        }
    }
}
if (!isset($_SESSION['system']['name']) || empty($_SESSION['system']['name'])) {
    $_SESSION['system']['name'] = "Crime/Complaints Reporting Management System";
}
if (!isset($_SESSION['system']['cover_img'])) {
    $_SESSION['system']['cover_img'] = "";
}
if (!isset($_SESSION['system']['about_content'])) {
    $_SESSION['system']['about_content'] = "";
}
if (!isset($_SESSION['system']['contact'])) {
    $_SESSION['system']['contact'] = "+1 800-555-CRMS";
}
if (!isset($_SESSION['system']['email'])) {
    $_SESSION['system']['email'] = "contact@crms-portal.org";
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include('header.php'); ?>

    <style>
      .bg-dark {
          background-color: #040404 !important;
      }
      #main-field{
        margin-top: 5rem!important;
      }
      body * {
        /*font-size: 13px ;*/
      }
      .modal-body  {
        color:black;
      }
      .fr-wrapper {
          color:white;
          background: #ffffff08;
          padding: 1em 1.5em;
          border-radius:5px;
      }

      .masthead{
        background: linear-gradient(to bottom, rgb(0 0 0 / 40%) 0%, rgb(245 242 240 / 45%) 100%), url('admin/assets/uploads/<?php echo !empty($_SESSION['system']['cover_img']) ? $_SESSION['system']['cover_img'] : '' ?>') !important;
        background-repeat: no-repeat !important;
        background-size: cover !important;
      }
    </style>
    <body id="page-top" class="bg-dark">
        <!-- Navigation-->
        <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
        </div>
      </div>
        <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
            <div class="container">
                <a class="navbar-brand js-scroll-trigger" href="./"><?php echo htmlspecialchars($_SESSION['system']['name']); ?></a>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto my-2 my-lg-0">
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="index.php?page=home">Home</a></li>
                        <?php if(isset($_SESSION['login_id'])): ?>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="index.php?page=complaint_list">My Complaint List</a></li>
                        <div class=" dropdown mr-4">
                            <a href="#" class="text-white dropdown-toggle"  id="account_settings" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo isset($_SESSION['login_name']) ? htmlspecialchars($_SESSION['login_name']) : 'User' ?> </a>
                              <div class="dropdown-menu" aria-labelledby="account_settings" style="left: -2.5em;">
                                <a class="dropdown-item" href="javascript:void(0)" id="manage_my_account"><i class="fa fa-cog"></i> Manage Account</a>
                                <a class="dropdown-item" href="admin/ajax.php?action=logout2"><i class="fa fa-power-off"></i> Logout</a>
                              </div>
                        </div>
                      <?php else: ?>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="javascript:void(0)" id="login_now">Login</a></li>
                      <?php endif; ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="admin/login.php"><i class="fa fa-shield-alt"></i> Admin</a></li>
                        <li class="nav-item"><a class="nav-link text-info" href="db_setup.php"><i class="fa fa-database"></i> DB Setup</a></li>
                    </ul>
                </div>
            </div>
        </nav>
  <header class="masthead">
      <div class="container-fluid h-100">
          <div class="row h-100 align-items-center justify-content-center text-center">
              <div class="col-lg-8 align-self-end mb-4 page-title">
                <h3 class="text-white">Welcome to <?php echo htmlspecialchars($_SESSION['system']['name']); ?></h3>
                  <hr class="divider my-4" />
              <div class="row mb-2 text-left justify-content-center ">
                 <button class="btn btn-primary" type="button" id="report_crime"><i class="fa fa-bullhorn mr-1"></i> Report a Crime/Complaint</button>
              </div>                        
              </div>
              
          </div>
      </div>
  </header>
  <main id="main-field" class="bg-dark">
        <?php 
        $page = isset($_GET['page']) ? basename($_GET['page']) : 'home';
        if (!file_exists($page.'.php')) {
            $page = 'home';
        }
        include $page.'.php';
        ?>
</main>
<div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog modal-full-height  modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span class="fa fa-arrow-right"></span>
        </button>
      </div>
      <div class="modal-body">
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
              <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
              <img src="" alt="">
      </div>
    </div>
  </div>
  <div id="preloader"></div>
        <footer class=" py-5 bg-dark">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="mt-0 text-white">Contact us</h2>
                        <hr class="divider my-4" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 ml-auto text-center mb-5 mb-lg-0">
                        <i class="fas fa-phone fa-3x mb-3 text-muted"></i>
                        <div class="text-white"><?php echo htmlspecialchars($_SESSION['system']['contact']) ?></div>
                    </div>
                    <div class="col-lg-4 mr-auto text-center">
                        <i class="fas fa-envelope fa-3x mb-3 text-muted"></i>
                        <a class="d-block text-white" href="mailto:<?php echo htmlspecialchars($_SESSION['system']['email']) ?>"><?php echo htmlspecialchars($_SESSION['system']['email']) ?></a>
                    </div>
                </div>
            </div>
            <br>
            <div class="container"><div class="small text-center text-muted">Copyright © <?php echo date('Y') ?> - <?php echo htmlspecialchars($_SESSION['system']['name']) ?></div></div>
        </footer>
        
       <?php include('footer.php') ?>
    </body>
    <script type="text/javascript">
      $('#login, #login_now').click(function(){
        uni_modal("Login",'login.php')
      })
      if(typeof $.fn.datetimepicker === 'function') {
        $('.datetimepicker').datetimepicker({
            format:'Y-m-d H:i',
        })
      }
      $('#find-car').submit(function(e){
        e.preventDefault()
        location.href = 'index.php?page=search&'+$(this).serialize()
      })
      $('#report_crime').click(function(){
        if('<?php echo !isset($_SESSION['login_id']) ? 1 : 0 ?>'==1){
          uni_modal("Login",'login.php');
          return false;
        }
          uni_modal("Report",'manage_report.php');
      })
      $('#manage_my_account').click(function(){
          uni_modal("Manage Account",'signup.php');
      })
    </script>
    <?php 
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        @$conn->close();
    }
    ?>
</html>
