<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="container-fluid">
	<form action="" id="login-frm">
		<div class="form-group">
			<label for="complainant_email" class="control-label">Email</label>
			<input type="email" id="complainant_email" name="email" required="" class="form-control" placeholder="user@example.com">
		</div>
		<div class="form-group">
			<label for="complainant_password" class="control-label">Password</label>
			<input type="password" id="complainant_password" name="password" required="" class="form-control" placeholder="••••••••">
			<small><a href="javascript:void(0)" id="new_account">Create New Account</a></small>
		</div>
		<button class="button btn btn-primary btn-sm">Login</button>
		<button class="button btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
	</form>
</div>

<style>
	#uni_modal .modal-footer{
		display:none;
	}
</style>

<script>
	$('#new_account').click(function(){
		uni_modal("Create an Account",'signup.php?redirect=index.php?page=home')
	})
	$('#login-frm').submit(function(e){
		e.preventDefault()
		start_load()
		if($(this).find('.alert-danger').length > 0 )
			$(this).find('.alert-danger').remove();
		$.ajax({
			url:'admin/ajax.php?action=login2',
			method:'POST',
			data:$(this).serialize(),
			error:function(err){
				console.log(err)
				end_load()
				$('#login-frm').prepend('<div class="alert alert-danger">An error occurred during authentication.</div>')
			},
			success:function(resp){
				if(resp == 1){
					location.href ='<?php echo isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php?page=home' ?>';
				}else{
					$('#login-frm').prepend('<div class="alert alert-danger">Email or password is incorrect.</div>')
					end_load()
				}
			}
		})
	})
</script>
