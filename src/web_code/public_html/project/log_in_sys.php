<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>自動導航車與高架搬運倉儲整合系統</title>
<style>
	.chi {font-family:"標楷體";font-size:20px;}
	th, button, input {font-size:24px;}
</style>
</head>

<body bgcolor="#D4FFFF">
<form action="" method=post>
<?php
	session_start();
	if (isset($_POST['member']))
	{
		$acc = $_POST['acc'];
		$pwd = $_POST['pwd'];
		if($acc == NULL || $pwd == NULL)
		{
			echo "<script>alert('請輸入帳號密碼')</script>";
		}
		else
		{
			include("database.php");
			$sql = "select count(*) from account where acc = '$acc'";
			$result = mysqli_query($con, $sql) or die("Error in query: $query.". mysqli_error());
			$n = mysqli_fetch_row($result);
			if($n[0] != 1)
			{
				echo "<script>alert('使用者帳號或密碼輸入錯誤')</script>";
			}
			else
			{
				mysqli_free_result($result);
				$sql = "select count('$acc') from account where pwd = '$pwd'";
				$result = mysqli_query($con, $sql) or die("Error in query: $query.". mysqli_error());
				$n = mysqli_fetch_row($result);
				if($n[0] != 1)
				{
					echo "<script>alert('使用者帳號或密碼輸入錯誤')</script>";
				}
				else
				{
					mysqli_free_result($result);
					$_SESSION['user'] = $acc;
					$sql = "select priority from account where acc = '$acc'";
					$result = mysqli_query($con, $sql) or die("Error in query: $query.". mysqli_error());
					$h = mysqli_fetch_row($result);
					$_SESSION['priority'] = $h[0];
					mysqli_free_result($result);
					
					if($h[0] == 3)
					{
						header('Location:../user.php');
					}
					else if($h[0] == 1 || $h[0] == 2)
					{
						header('Location:./pwd/sys.php');
					}
				}
			}
			
			mysqli_close($con);
		}

	}
?>
<center>
<table border=5 cellspacing=0 bordercolor="#6363FF" cellpadding=10 width=50%>
<tr>
	<th>
	自動導航車與高架搬運倉儲整合系統
	</th>
</tr>
<tr>
	<th>
	帳號 : <input type=text name="acc" style="width:300px;height:10dpx;" maxlength="20"><br><br>
	密碼 : <input type=password name="pwd" style="width:300px;height:10dpx;" maxlength="20">
	</th>
</tr>
<tr>
	<th>
	<input type="submit" Name="member" value="提交" /> 
	</th>
</tr>
</table><p>

</center>

</form></body>
</html>
