<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>Modbus Master Example</title>
	</head>
	<?	
		include("database.php");
	?>
	<?
						if (isset($_POST['add']))
						{
							$query = "delete from tt"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
							
							
							$r = $_POST['r'];
							$g = $_POST["g"];
							$b = $_POST["b"];
							
							if($r == NULL)
								$r=0;
							if($g == NULL)
								$b=0;
							if($b == NULL)
								$b=0;
							
							if($r > 256)
							{
								$rd = 255;
								$r = sprintf("%04s",dechex(255));
							}
							else if($r < 0)
							{
								$rd = 0;
								$r = sprintf("%04s",0);
							}
							else
							{
								$rd = $r;
								$r = sprintf("%04s",dechex($r));
							}
							echo $r;
							
							if($g > 256)
							{
								$gd = 255;
								$g = sprintf("%04s",dechex(255));
							}
							else if($g < 0)
							{
								$gd = 0;
								$g = sprintf("%04s",0);
							}
							else
							{
								$gd = $g;
								$g = sprintf("%04s",dechex($g));
							}
							
							if($b > 256)
							{
								$bd = 255;
								$b = sprintf("%04s",dechex(255));
							}
							else if($b < 0)
							{
								$bd = 0;
								$b = sprintf("%04s",0);
							}
							else
							{
								$bd = $b;
								$b = sprintf("%04s",dechex($b));
							}
							
							$query = "insert into tt value($rd,$gd,$bd)"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
						}
					?>	
	<body bgcolor="#D4FFFF">
				<form action="" method=post>
					<table border="1" width="1080" align="center">
						<tr height = "50">
							<th colspan="2"  bgcolor="#97CBFF" width='540'>
								<p style="font-size:20px">Modbus Master Example</p>
							</th>
						</tr>
						<tr height = "50" >
							<th>Message generated</th>
							<th>
							<?
								
								if (isset($_POST['add']))
								{
									
									$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
									$message = "01100000000306".$r.$g.$b;
									$num = strlen($message);
									$num %= 4;
									if($num == 2)
									{
										$message .= sprintf("%02X", 0);
									}
									$str = "./modbus fe80::fdff:ffff:f45a:e2d ".strtoupper($message);
									$response = exec($str, $output, $ret);
									echo $str."<br>";
								}
							?>
							</th>
						</tr>
						<tr height = "50">
							<th colspan='2'>目前資料</th>
						</tr>
						<tr height = "50">
							<th>
								Red
							</th>
							<th>
								<?
									
									$sql = "SELECT red FROM  tt ";
									$result = mysqli_query($con, $sql);
									list($Red) = mysqli_fetch_row($result);
									echo $Red;
									mysqli_free_result($result);
								?>
							</th>
						</tr>
						<tr height = "50">
							<th>
								Green
							</th>
							<th>
								<?
									
									$sql = "SELECT green FROM  tt ";
									$result = mysqli_query($con, $sql);
									list($Green) = mysqli_fetch_row($result);
									echo $Green;
									mysqli_free_result($result);
								?>
							</th>
						</tr>
						<tr height = "50">
							<th>
								Blue
							</th>
							<th>
								<?
									
									$sql = "SELECT blue FROM  tt ";
									$result = mysqli_query($con, $sql);
									list($Blue) = mysqli_fetch_row($result);
									echo $Blue;
									mysqli_free_result($result);
								?>
							</th>
						</tr>
						<tr height = "50">
							<th>
								Red
							</th>
							<th>
								<input type="text" name="r">
							</th>
						</tr>
						<tr height = "50">
							<th>
								Green
							</th>
							<th>
								<input type="text" name="g">
							</th>
						</tr>
						<tr height = "50">
							<th>
								Blue
							</th>
							<th>
								<input type="text" name="b">
							</th>
						</tr>
						<tr  bgcolor="#D4FFFF" height = "50">
							<th colspan="2" >
								<button type="submit" name="add" style="align;">提交</button>&emsp;
								<input type="button" value="返回系統操作功能" onclick="location.href='./sys.php'" style="align;"></button>
							</th>
						
						</tr>
							
					</table>
					
					
					
					
					
				</form>
	</body>
</html>