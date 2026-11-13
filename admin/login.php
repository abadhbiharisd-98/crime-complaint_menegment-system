<!DOCTYPE html>
<html lang="en">
<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('./db_connect.php');
ob_start();
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $res = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($res && $res instanceof mysqli_result && $res->num_rows > 0) {
        $system = $res->fetch_array();
        if (is_array($system)) {
            foreach ($system as $k => $v) {
                if (!is_numeric($k))
                    $_SESSION['system'][$k] = $v;
            }
        }
    }
}
if (!isset($_SESSION['system']['name']) || empty($_SESSION['system']['name'])) {
    $_SESSION['system']['name'] = "Crime/Complaints Reporting Management System";
}
ob_end_flush();
?>
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?php echo htmlspecialchars($_SESSION['system']['name']); ?> - Admin Login</title>
 	

<?php include('./header.php'); ?>
<?php 
if(isset($_SESSION['login_id']))
header("location:index.php?page=home");
?>

</head>
<style>
	body{
		width: 100%;
	    height: 100vh;
	    margin: 0;
	    background: #1a1a1a;
	}
	main#main{
		width:100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.card {
		border-radius: 10px;
		box-shadow: 0 4px 20px rgba(0,0,0,0.5);
	}
</style>

<body class="bg-dark">

  <main id="main">
  		<div class="align-self-center w-100">
		<h4 class="text-white text-center mb-4"><b><?php echo htmlspecialchars($_SESSION['system']['name']); ?></b><br><small class="text-muted">Administrator Portal</small></h4>
  		<div id="login-center" class="row justify-content-center">
  			<div class="card col-md-4 bg-light text-dark">
  				<div class="card-body">
  					<form id="login-form">
  						<div class="form-group">
  							<label for="username" class="control-label font-weight-bold">Username</label>
  							<input type="text" id="username" name="username" class="form-control" required placeholder="admin">
  						</div>
  						<div class="form-group">
  							<label for="password" class="control-label font-weight-bold">Password</label>
  							<input type="password" id="password" name="password" class="form-control" required placeholder="admin123">
  						</div>
  						<center>
  							<button type="submit" class="btn btn-primary btn-block">Login</button>
  						</center>
  						<hr>
  						<div class="d-flex justify-content-between">
  							<a href="../index.php" class="small text-secondary"><i class="fa fa-arrow-left"></i> Home Page</a>
  							<a href="../db_setup.php" class="small text-info"><i class="fa fa-database"></i> DB Setup</a>
  						</div>
  					</form>
  				</div>
  			</div>
  		</div>
  		</div>
  </main>

  <a href="#" class="back-to-top"><i class="icofont-simple-up"></i></a>

</body>
<script>
	$('#login-form').submit(function(e){
		e.preventDefault();
		var btn = $(this).find('button[type="submit"]');
		btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Logging in...');
		if($(this).find('.alert-danger').length > 0 )
			$(this).find('.alert-danger').remove();
		$.ajax({
			url:'ajax.php?action=login',
			method:'POST',
			data:$(this).serialize(),
			error:function(err){
				console.log(err);
				btn.removeAttr('disabled').html('Login');
				$('#login-form').prepend('<div class="alert alert-danger">An error occurred during authentication. Check database connection.</div>');
			},
			success:function(resp){
				if(resp == 1){
					location.href ='index.php?page=home';
				}else{
					$('#login-form').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>');
					btn.removeAttr('disabled').html('Login');
				}
			}
		});
	});
</script>	
</html>
