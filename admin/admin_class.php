<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 0);

Class Action {
	private $db;

	public function __construct() {
		ob_start();
		include 'db_connect.php';
		$this->db = (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) ? $conn : null;
	}

	function __destruct() {
		if ($this->db && $this->db instanceof mysqli) {
			@$this->db->close();
		}
		if (ob_get_level() > 0) {
			@ob_end_flush();
		}
	}

	function login(){
		extract($_POST);
		if(!isset($username) || !isset($password) || !$this->db) {
			return 3;
		}
		$u = $this->db->real_escape_string($username);
		$p = md5($password);
		$qry = $this->db->query("SELECT * FROM users where username = '{$u}' and password = '{$p}' ");
		if($qry && $qry instanceof mysqli_result && $qry->num_rows > 0){
			$row = $qry->fetch_array();
			if(is_array($row)){
				foreach ($row as $key => $value) {
					if($key != 'password' && $key != 'passwors' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
			}
			return 1;
		}else{
			return 3;
		}
	}

	function login2(){
		extract($_POST);
		if(!isset($email) || !isset($password) || !$this->db) {
			return 3;
		}
		$e = $this->db->real_escape_string($email);
		$p = md5($password);
		$qry = $this->db->query("SELECT * FROM complainants where email = '{$e}' and password = '{$p}' ");
		if($qry && $qry instanceof mysqli_result && $qry->num_rows > 0){
			$row = $qry->fetch_array();
			if(is_array($row)){
				foreach ($row as $key => $value) {
					if($key != 'password' && $key != 'passwors' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
			}
			return 1;
		}else{
			return 3;
		}
	}

	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}

	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../index.php");
	}

	function save_user(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$name = isset($name) ? $this->db->real_escape_string($name) : '';
		$username = isset($username) ? $this->db->real_escape_string($username) : '';
		$type = isset($type) ? (int)$type : 3;

		$data = " name = '$name' ";
		$data .= ", username = '$username' ";
		if(!empty($password))
			$data .= ", password = '".md5($password)."' ";
		$data .= ", type = '$type' ";

		$chk = $this->db->query("SELECT * FROM users where username = '$username' and id !='$id' ");
		if($chk && $chk instanceof mysqli_result && $chk->num_rows > 0){
			return 2;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users SET ".$data);
		}else{
			$save = $this->db->query("UPDATE users SET ".$data." WHERE id = ".$id);
		}
		if($save){
			return 1;
		}
		return 0;
	}

	function delete_user(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$delete = $this->db->query("DELETE FROM users WHERE id = ".$id);
		if($delete)
			return 1;
		return 0;
	}

	function signup(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$name = isset($name) ? $this->db->real_escape_string($name) : '';
		$email = isset($email) ? $this->db->real_escape_string($email) : '';
		$address = isset($address) ? $this->db->real_escape_string($address) : '';
		$contact = isset($contact) ? $this->db->real_escape_string($contact) : '';

		$data = " name = '$name' ";
		$data .= ", email = '$email' ";
		$data .= ", address = '$address' ";
		$data .= ", contact = '$contact' ";
		if(!empty($password))
			$data .= ", password = '".md5($password)."' ";

		$chk = $this->db->query("SELECT * FROM complainants WHERE email ='$email' ".(!empty($id) ? " and id != '$id' " : ''));
		if($chk && $chk instanceof mysqli_result && $chk->num_rows > 0){
			return 3;
		}
		if(empty($id))
			$save = $this->db->query("INSERT INTO complainants SET $data");
		else
			$save = $this->db->query("UPDATE complainants SET $data WHERE id=$id ");

		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			$qry = $this->db->query("SELECT * FROM complainants WHERE id = $id ");
			if($qry && $qry instanceof mysqli_result && $qry->num_rows > 0){
				$row = $qry->fetch_array();
				if(is_array($row)){
					foreach ($row as $key => $value) {
						if($key != 'password' && $key != 'passwors' && !is_numeric($key))
							$_SESSION['login_'.$key] = $value;
					}
				}
				return 1;
			}else{
				return 3;
			}
		}
		return 0;
	}

	function update_account(){
		extract($_POST);
		if(!$this->db) return 0;
		$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
		$firstname = isset($firstname) ? $this->db->real_escape_string($firstname) : '';
		$lastname = isset($lastname) ? $this->db->real_escape_string($lastname) : '';
		$email = isset($email) ? $this->db->real_escape_string($email) : '';

		$data = " name = '".$firstname.' '.$lastname."' ";
		$data .= ", username = '$email' ";
		if(!empty($password))
			$data .= ", password = '".md5($password)."' ";

		$chk = $this->db->query("SELECT * FROM users WHERE username = '$email' AND id != '$login_id' ");
		if($chk && $chk instanceof mysqli_result && $chk->num_rows > 0){
			return 2;
		}
		$save = $this->db->query("UPDATE users SET $data WHERE id = '$login_id' ");
		if($save){
			return 1;
		}
		return 0;
	}

	function save_settings(){
		extract($_POST);
		if(!$this->db) return 0;
		$name = isset($name) ? $this->db->real_escape_string(str_replace("'","&#x2019;",$name)) : '';
		$email = isset($email) ? $this->db->real_escape_string($email) : '';
		$contact = isset($contact) ? $this->db->real_escape_string($contact) : '';
		$about = isset($about) ? $this->db->real_escape_string(htmlentities(str_replace("'","&#x2019;",$about))) : '';

		$data = " name = '$name' ";
		$data .= ", email = '$email' ";
		$data .= ", contact = '$contact' ";
		$data .= ", about_content = '$about' ";

		if(isset($_FILES['img']['tmp_name']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$targetDir = __DIR__ . '/assets/uploads/';
			if(!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
			@move_uploaded_file($_FILES['img']['tmp_name'], $targetDir . $fname);
			$data .= ", cover_img = '$fname' ";
		}
		
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk && $chk instanceof mysqli_result && $chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings SET ".$data);
		}else{
			$save = $this->db->query("INSERT INTO system_settings SET ".$data);
		}
		if($save){
			$query = $this->db->query("SELECT * FROM system_settings LIMIT 1");
			if($query && $query instanceof mysqli_result && $query->num_rows > 0){
				$res = $query->fetch_array();
				if(is_array($res)){
					foreach ($res as $key => $value) {
						if(!is_numeric($key))
							$_SESSION['system'][$key] = $value;
					}
				}
			}
			return 1;
		}
		return 0;
	}

	function save_page_img(){
		extract($_POST);
		if(isset($_FILES['img']['tmp_name']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$targetDir = __DIR__ . '/assets/uploads/';
			if(!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
			$move = @move_uploaded_file($_FILES['img']['tmp_name'], $targetDir . $fname);
			if($move){
				$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
				$hostName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
				$base_url = getenv('BASE_URL') ?: ($protocol . '://' . $hostName);
				return json_encode(array('link' => rtrim($base_url, '/') . '/admin/assets/uploads/' . $fname));
			}
		}
		return json_encode(array('error' => 'Upload failed'));
	}

	function save_responder(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$name = isset($name) ? $this->db->real_escape_string($name) : '';
		$station_id = isset($station_id) ? (int)$station_id : 0;
		$availability = isset($availability) ? (int)$availability : 1;

		$check = $this->db->query("SELECT * FROM responders_team WHERE name ='$name' AND station_id = '$station_id' ".(!empty($id) ? " and id != {$id} " : ''));
		if($check && $check instanceof mysqli_result && $check->num_rows > 0){
			return 2;
		}

		$data = " name='$name', station_id='$station_id', availability='$availability' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO responders_team SET $data");
		}else{
			$save = $this->db->query("UPDATE responders_team SET $data WHERE id = $id");
		}

		if($save){
			return 1;
		}
		return 0;
	}

	function delete_responder(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$delete = $this->db->query("DELETE FROM responders_team WHERE id = ".$id);
		if($delete){
			return 1;
		}
		return 0;
	}

	function save_station(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$name = isset($name) ? $this->db->real_escape_string($name) : '';
		$address = isset($address) ? $this->db->real_escape_string($address) : '';

		$check = $this->db->query("SELECT * FROM stations WHERE name ='$name' ".(!empty($id) ? " and id != {$id} " : ''));
		if($check && $check instanceof mysqli_result && $check->num_rows > 0){
			return 2;
		}

		$data = " name='$name', address='$address' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO stations SET $data");
		}else{
			$save = $this->db->query("UPDATE stations SET $data WHERE id = $id");
		}

		if($save){
			return 1;
		}
		return 0;
	}

	function delete_station(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$delete = $this->db->query("DELETE FROM stations WHERE id = ".$id);
		if($delete){
			return 1;
		}
		return 0;
	}

	function verify(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$status = isset($status) ? (int)$status : 0;
		$save = $this->db->query("UPDATE complainants SET status = $status WHERE id = $id");
		if($save)
			return 1;
		return 0;
	}

	function complaint(){
		extract($_POST);
		if(!$this->db) return 0;
		$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
		$message = isset($message) ? $this->db->real_escape_string($message) : '';
		$address = isset($address) ? $this->db->real_escape_string($address) : '';

		$data = " complainant_id = '$login_id' ";
		$data .= ", message ='$message' ";
		$data .= ", address ='$address' ";

		$save = $this->db->query("INSERT INTO complaints SET $data");
		if($save)
			return 1;
		return 0;
	}

	function manage_complaint(){
		extract($_POST);
		if(!$this->db) return 0;
		$id = isset($id) ? (int)$id : 0;
		$status = isset($status) ? (int)$status : 0;
		$responder_id = isset($responder_id) ? (int)$responder_id : 0;
		$remarks = isset($remarks) ? $this->db->real_escape_string($remarks) : '';
		$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;

		$save = $this->db->query("UPDATE complaints SET status = $status WHERE id = $id");
		if($save){
			$chk = $this->db->query("SELECT * FROM complaints_action WHERE complaint_id = $id ");
			$hasAction = ($chk && $chk instanceof mysqli_result && $chk->num_rows > 0);
			if($status == 2){
				$data = " complaint_id = $id ";
				$data .= ", responder_id = $responder_id ";
				$data .= ", dispatched_by = $login_id ";
				if($hasAction){
					$res = $chk->fetch_array();
					$save2 = $this->db->query("UPDATE complaints_action SET $data WHERE complaint_id = $id");
				}else{
					$save2 = $this->db->query("INSERT INTO complaints_action SET $data");
				}
				if($save2){
					$this->db->query("UPDATE responders_team SET availability = 0 WHERE id = $responder_id ");
				}
				if(isset($res) && is_array($res) && isset($res['responder_id'])){
					$this->db->query("UPDATE responders_team SET availability = 1 WHERE id = {$res['responder_id']} ");
				}
				return 1;
			}elseif($status == 3){
				if($hasAction){
					$save2 = $this->db->query("UPDATE complaints_action SET status = 1, remarks = '$remarks' WHERE complaint_id = $id");
					if($save2){
						$this->db->query("UPDATE responders_team SET availability = 1 WHERE id = $responder_id ");
						return 1;
					}
				}else{
					return 2;
				}
			}else{
				$this->db->query("DELETE FROM complaints_action WHERE complaint_id = $id ");
				if(isset($res) && is_array($res) && isset($res['responder_id'])){
					$this->db->query("UPDATE responders_team SET availability = 1 WHERE id = {$res['responder_id']} ");
				}
				return 1;
			}
		}
		return 0;
	}
}
