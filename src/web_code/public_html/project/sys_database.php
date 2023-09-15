<?
	session_start();
	$host = "localhost"; 
	$user = "m1022009"; 
	$pass = "m1022009"; 
	$db = "test"; 	   	
	$con=mysqli_connect($host, $user, $pass,$db) or die("Unable to connect!"); 
	$result = mysqli_select_db($con, "test") or die("無法選取資料庫");//選擇資料庫 
	
	$user = $_SESSION['user'];
	$sql = "select count(*) from account where acc = '$user'";
	$result = mysqli_query($con, $sql) or die("Error in query: $query.". mysqli_error());
	$n = mysqli_fetch_row($result);
	if($n[0] != 1)
	{
		//echo "<script>alert('非法進入')</script>";
		header("Location:/~Wi_SUN/index.php");
	}
	$priority = $_SESSION['priority'];
	mysqli_free_result($result);
?>