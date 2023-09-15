<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>使用者管理</title>
	</head>
<?	
	include("sys_database.php");
	$priority = $_SESSION['priority'];
	if($priority != 1)
	{
		unset($_SESSION['user']);
		unset($_SESSION['priority']);
		header("Location:index.php");
	}
?>
	<body>
				<form action="" method=post>
					<table border="1" width="80%" align="center">
						<tr height = "50">
							<th colspan="2"  bgcolor="#97CBFF">
								<font size=5>使用者管理功能</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC" height = "50">
							<th  colspan="2">
								<button type="submit" value="1" Name="set">新增帳號</button>
								<button type="submit" value="2" Name="set">修改帳號</button>
								<button type="submit" value="3" Name="set">刪除帳號</button>
								<input type="button" value="返回系統管理功能" onclick="location.href='./sys.php'" style="align;" ></button>
							</th>
						
						</tr>
					</table>
					
					<?
						if(isset($_POST['set']))
						{
							$set = $_POST['set'];
							switch($set)
							{
								case '1' : 
									echo "<table border='2' width='80%' align='center' height='10'><br><tr bgcolor='#97CBFF'>";
									echo "<th colspan='2'><font size=5>新增人員</font>";
									echo "</th></tr>";
									echo "<tr><th  width='20%'>登入資訊</th><th>";
									echo "帳號&nbsp&nbsp:&nbsp&nbsp<input type='text' Name='acc' size='8'/ >&nbsp&nbsp";
									echo "密碼&nbsp&nbsp:&nbsp&nbsp<input type='text' Name='pwd' size='8'/ >&nbsp&nbsp";
									echo "</th><tr><th  width='20%' >管理層級</th><th>";
									echo "<input type='radio' name='id' value='2'>一般管理人員　<input type='radio' name='id' value='3'>操作人員</th></tr>";
									echo "<tr><th colspan='2'><input type='submit' Name='add' value='提交' />";
									echo "</th></tr></table>";
									break;
								case '3' :
									echo "<table border='2' width='80%' align='center'><br><tr bgcolor='#97CBFF'>";
									echo "<th><font size=5>刪除操作人員</font>";
									echo "</th></tr>";
									echo "<tr><th>";
									echo "帳號&nbsp&nbsp:&nbsp&nbsp<input type='text' Name='acc' size='8'/ >&nbsp&nbsp";
									echo "<input type='submit' Name='del' value='提交' />";
									echo "</th></tr></table>";
									break;
									
								case '2' :
									echo "<table border='2' width='80%' align='center'><br><tr bgcolor='#97CBFF'>";
									echo "<th><font size=5>變更密碼</font>";
									echo "</th></tr>";
									echo "<tr><th>";
									echo "帳號&nbsp&nbsp:&nbsp&nbsp<input type='text' Name='acc' size='8'/ >&nbsp&nbsp";
									echo "密碼&nbsp&nbsp:&nbsp&nbsp<input type='text' Name='pwd' size='8'/ >&nbsp&nbsp";
									echo "<input type='submit' Name='alter' value='提交' />";
									echo "</th></tr></table>";

									break;
							}
						}
					?>
								
									<table border='2' width='80%' align='center'><br>
									<tr bgcolor='#97CBFF'><th COLSPAN=3 ALIGN=CENTER>
									<font size=5>資料顯示</font></th></tr>
									<tr  bgcolor='#FCFCFC'><th width='240'>帳號</th><th width='300'>身分別</th>
									<?
										include("database.php");	
										$sql = "SELECT acc,priority FROM  account";
										$result = mysqli_query($con, $sql);

										$i=0;
										while(list($acc[$i], $id[$i]) = mysqli_fetch_row($result))
										$i ++;

										for($j = 0; $j < $i; $j++)
										{
 											
											echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$acc[$j]. "</th><th>";
											switch($id[$j])
											{
												case 1:
													echo "超級管理人員";
													break;
												case 2:
													echo "一般管理人員";
													break;
												case 3:
													echo "操作人員";
													break;
											}
											
											echo "</th></tr>";
											unset($ch);
										}
										mysqli_free_result($result);
										mysqli_close($con);	
										echo "</table>";
									?>

					

				
					<?php
						include("sys_database.php");
						if(isset($_POST['add']))
						{ 
							$acc = $_POST['acc'];
							$pwd = $_POST['pwd'];
							$id = $_POST['id'];
							if($acc == NULL ||$pwd == NULL || $id == NULL)
							{
								echo "<script>alert('帳號和密碼不能為空');</script>";
							}
							else
							{
								$sql = "select count(*) from account where acc = '$acc'";
								$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
								$n = mysqli_fetch_row($result);
								$i = 0;
								if($n[0] != 0)
								{
									$i++;
									echo "<script>alert('重複出現 帳號 :".$acc."');</script>";
								}
								else
								{	
									mysqli_free_result($result);
									$query = "INSERT INTO account(acc,pwd,priority) VALUE('$acc','$pwd','$id')"; 
									$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
									echo "<script>alert('新增成功');</script>";
								}
							}
							mysqli_free_result($result);
							mysqli_close($con);	
							header("refresh: 0;");
						} 

						if(isset($_POST['del']))
						{ 
							$acc = $_POST['acc'];
							if($acc == NULL)
							{
								echo "<script>alert('帳號不能為空');</script>";
							}
							else if($acc == 'chi')
							{
								echo "<script>alert('此帳號無法刪除');</script>";
							}
							else
							{
								$query = "delete from account where acc='$acc'"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
								mysqli_free_result($result);
								echo "<script>alert('刪除成功');</script>";
							}
							mysqli_close($con); 
							header("refresh: 0;");
						} 
						
						if(isset($_POST['alter']))
						{
							$acc = $_POST['acc'];
							$pwd = $_POST['pwd'];
							$query = "update account set pwd = '$pwd' where acc = '$acc'"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
							mysqli_close($con); 
							header("refresh: 0;");
						}
					?>

				</form>
	</body>
</html>