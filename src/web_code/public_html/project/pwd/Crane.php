<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" referrerpolicy="no-referrer"></script>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>OHT　設備操作功能</title>
<style>
	a:hover {background-color:blue;color:white;}
	a {text-decoration:none;}
	.chi {font-family:"標楷體";font-size:24px;}
	th, button, input {font-size:24px;}
	table{
		border-collapse: separate;
		border-spacing: 0;
		border-color:white;
	}
	
	th, tr {
		border: 3px solid ;
		border-color: #b3ecff;
		border-collapse: separate;
		border-spacing: 0;
		font-size: 30px;		
	}
	/*第一欄最後列：左上*/
	tr:first-child th:first-child
	{
		border-top-left-radius: 20px;
	}
	/*第一欄最後列：左下*/
	tr:last-child th:first-child
	{
		border-bottom-left-radius: 20px;
	}
	/*最後欄第一列：右上*/
	tr:first-child th:last-child
	{
		border-top-right-radius: 20px;
	}
	/*最後欄第一列：右下*/
	tr:last-child th:last-child
	{
		border-bottom-right-radius: 20px;
	}
</style>
</head>
<body onload = "table()">

<script type="text/javascript">
				
	function table(){
		const xhttp = new XMLHttpRequest();
		xhttp.onload = function(){
		document.getElementById("table").innerHTML = this.responseText;
		}
		xhttp.open("GET", "Crane_data.php");
		xhttp.send();
	}

	setInterval(function(){
		table();
	}, 200);
				
</script>	
<?	
	include("sys_database.php");
?>
&nbsp;<br>
<form action="./Crane.php" method="POST">
	
<input type=hidden name=action value=1>
	<center>
	
	<table border=3 width=80% cellspacing=2 cellpadding=10 bordercolor=Blue>
	<tr>
		<th height=30 colspan=2 bgcolor="#66d9ff">
			<font size=8>OHT　設　備　操　作　功　能</font>
		</th>
	</tr>
	</table><p>
	<?
		if(isset($_POST['ch_crane']))
		{
			$_SESSION['operator'] = 0;
			$ch_crane = $_POST['ch_crane'];
			unset($_SESSION['operator_ip']);
		}
		else if(isset($_POST['ok']))
		{
			$operator_name = $_POST['operator_ch'];
			$_SESSION['operator'] = 1;
			$sql = "select IP from Wi_SUN_IP where Name = '$operator_name'";
			$result = mysqli_query($con, $sql);
			$operator_ip = mysqli_fetch_row($result);
			
			$_SESSION['operator_ip'] = $operator_ip[0];
			mysqli_free_result($result);
		}
		else if(isset($_POST['Motor_move_dis']) || isset($_POST['gohome']) || isset($_POST['Motor_move_dir']) || isset($_POST['EMT']))
		{
			$_SESSION['operator'] = 1;
		}
		else
		{
			$_SESSION['operator'] = 0;
		}

		if($_SESSION['operator'] == 1)
		{
			$ip = $_SESSION['operator_ip'];
			//echo "$ip";
			echo "<table border=3 width=80% cellspacing=2 cellpadding=10 bordercolor=Blue>
			<form action='' method='POST'>
			<input type=hidden name=action value=1>";
			
			function to_binary($buf, $start, $end)
			{
				$data = 0;
				for ($i = $start; $i <= $end; $i ++)
				{
					if (($buf[$i] >= '0') && ($buf[$i] <= '9'))
						$data = $data * 16 + (ord($buf[$i]) - ord('0'));
					else if (($buf[$i] >= 'A') && ($buf[$i] <= 'F'))
						$data = $data * 16 + (ord($buf[$i]) - ord('A') + 10);
				}
				
				return $data;
			}
			
			if (isset($_POST["action"]))
			{
				if (isset($_POST["gohome"]))
				{
					$message = "0110000700020400040000";	// 步進馬達歸位
				}
				else if (isset($_POST["Motor_move_dis"]))	// 步進馬達移動，固定距離
				{
					$message = "01100007000204";
					$dis = $_POST["Motor_move_dis"];
					switch ($dis)
					{
						case 0: //50 mm X_forward
							$message .= sprintf("%04X", 7);
							$message .= sprintf("%04X", 50);
							break;
							
						case 1: //5 mm X_forward
							$message .= sprintf("%04X", 7);
							$message .= sprintf("%04X", 5);
							break;
							
						case 2: //1 mm X_forward
							$message .= sprintf("%04X", 7);
							$message .= sprintf("%04X", 1);
							break;
							
						case 3: //50 mm X_backward
							$message .= sprintf("%04X", 8);
							$message .= sprintf("%04X", 50);
							break;
						case 4: //5 mm X_backward
							$message .= sprintf("%04X", 8);
							$message .= sprintf("%04X", 5);
							break;
							
						case 5: //1 mm X_backward
							$message .= sprintf("%04X", 8);
							$message .= sprintf("%04X", 1);
							break;
							
						case 6: //50 mm Y_forward
							$message .= sprintf("%04X", 9);
							$message .= sprintf("%04X", 50);
							break;
							
						case 7: //5 mm Y_forward
							$message .= sprintf("%04X", 9);
							$message .= sprintf("%04X", 5);
							break;	
							
						case 8: //1 mm Y_forward
							$message .= sprintf("%04X", 9);
							$message .= sprintf("%04X", 1);
							break;
							
						case 9: //50 mm Y_backward
							$message .= sprintf("%04X", 10);
							$message .= sprintf("%04X", 50);
							break;
							
						case 10: //5 mm Y_backward
							$message .= sprintf("%04X", 10);
							$message .= sprintf("%04X", 5);
							break;
							
						case 11: //1 mm Y_backward
							$message .= sprintf("%04X", 10);
							$message .= sprintf("%04X", 1);
							break;	
							
						case 12: //100 mm Z_UP
							$message .= sprintf("%04X", 11);
							$message .= sprintf("%04X", 100);
							break;
							
						case 13: //10 mm Z_UP
							$message .= sprintf("%04X", 11);
							$message .= sprintf("%04X", 10);
							break;	
							
						case 14: //1 mm Z_UP
							$message .= sprintf("%04X", 11);
							$message .= sprintf("%04X", 1);
							break;
							
						case 15: //100 mm Z_DOWN
							$message .= sprintf("%04X", 12);
							$message .= sprintf("%04X", 100);
							break;
							
						case 16: //10 mm Z_DOWN
							$message .= sprintf("%04X", 12);
							$message .= sprintf("%04X", 10);
							break;
						case 17: //1 mm Z_DOWN
							$message .= sprintf("%04X", 12);
							$message .= sprintf("%04X", 1);
							break;
						case 18: //10 mm W_forward
							$message .= sprintf("%04X", 5);
							$message .= sprintf("%04X", 10);
							break;
						case 19: //5 mm  W_forward
							$message .= sprintf("%04X", 5);
							$message .= sprintf("%04X", 5);
							break;
						case 20: //1 mm  W_forward
							$message .= sprintf("%04X", 5);
							$message .= sprintf("%04X", 1);
							break;
						case 21: //10 mm W_backward
							$message .= sprintf("%04X", 6);
							$message .= sprintf("%04X", 10);
							break;
						case 22: //5 mm W_backward
								$message .= sprintf("%04X", 6);
								$message .= sprintf("%04X", 5);
								break;
						case 23: //1 mm W_backward
							$message .= sprintf("%04X", 6);
							$message .= sprintf("%04X", 1);
							break;
					}
				}
				else if (isset($_POST["Motor_move_dir"]))	// 步進馬達移動，手動輸入距離
				{
					$message = "01100007000204";
					$set_motor = $_POST["Motor_move_dir"];
					$dir = $_POST["Motor"] + $set_motor;
					$set_dis = $_POST["Set_Motor_dis"];
					$message .= sprintf("%04X", $dir);
					$message .= sprintf("%04X", $set_dis);
				}
				else if (isset($_POST["EMT"]))
				{
					$S_EMT = $_POST["EMT"];
					switch($S_EMT)
					{
						case 0:
							$message = "011000070001020002";
							break;
						case 1:
							$message = "011000070001020003";
							break;
					}
				}
				// 送出訊息
				
				$tr_rand = rand(0, 65535);
				$tr_rand = sprintf("%04X", $tr_rand);

				$num = sprintf("%04X", strlen($message) / 2);
				$message = "0000" . $num .  $message . "00" ;
				$sql = "insert into Command value(0, '$tr_rand', '$ip', '$message', 0, NOW())";
				if(isset($_POST["EMT"]) || isset($_POST["Motor_move_dir"]) ||
				isset($_POST["Motor_move_dis"]) || isset($_POST["gohome"]))
				{
					$result = mysqli_query($con, $sql);
				}

				$tr_rand = rand(0, 65535);
				$tr_rand = sprintf("%04X", $tr_rand);
				$message = "00000006010300010006" ;
				$sql = "insert into Command value(0, '$tr_rand', '$ip', '$message', 0, NOW())";
				$result = mysqli_query($con, $sql);

			}
		

			echo "<tr>
				<th colspan='2'>
					<button type='submit' name='gohome' value=0 style='width:180px;height:40px;'>步進馬達歸位</button>&nbsp;&nbsp;&nbsp;&nbsp;
					<button type='submit' name='ch_crane' value=0 style='width:280px;height:40px;'>選擇其他 OHT 設備</button>&nbsp;&nbsp;&nbsp;&nbsp;
				</th>
			</tr>

			<tr>
				<th>W 軸馬達</th>
				<th>
					<button type=submit name=Motor_move_dis value=18 style='width:180px;height:40px;'>前進 10 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=19 style='width:180px;height:40px;'>前進 5 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=20 style='width:180px;height:40px;'>前進 1 mm</button>&emsp;
					
					<button type=submit name=Motor_move_dis value=21 style='width:180px;height:40px;'>後退 10 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=22 style='width:180px;height:40px;'>後退 5 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=23 style='width:180px;height:40px;'>後退 1 mm</button>
				</th>
			</tr>
			<tr>
				<th>X 軸馬達</th>
				<th>
					<button type=submit name=Motor_move_dis value=0 style='width:180px;height:40px;'>前進 50 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=1 style='width:180px;height:40px;'>前進 5 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=2 style='width:180px;height:40px;'>前進 1 mm</button>&emsp;
					
					<button type=submit name=Motor_move_dis value=3 style='width:180px;height:40px;'>50 mm 後退</button>&nbsp;
					<button type=submit name=Motor_move_dis value=4 style='width:180px;height:40px;'>5 mm 後退</button>&nbsp;
					<button type=submit name=Motor_move_dis value=5 style='width:180px;height:40px;'>1 mm 後退</button>
				</th>
			</tr>	

			<tr>
				<th>Y 軸馬達</th>
				<th>
					<button type=submit name=Motor_move_dis value=6 style='width:180px;height:40px;'>前進 50 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=7 style='width:180px;height:40px;'>前進 5 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=8 style='width:180px;height:40px;'>前進 1 mm</button>&emsp;
					
					<button type=submit name=Motor_move_dis value=9 style='width:180px;height:40px;'>50 mm 後退</button>&nbsp;
					<button type=submit name=Motor_move_dis value=10 style='width:180px;height:40px;'>5 mm 後退</button>&nbsp;
					<button type=submit name=Motor_move_dis value=11 style='width:180px;height:40px;'>1 mm 後退</button>
				</th>
			</tr>

			<tr>
				<th>Z 軸馬達</th>
				<th>
					<button type=submit name=Motor_move_dis value=12 style='width:180px;height:40px;'>向下 100 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=13 style='width:180px;height:40px;'>向下 10 mm</button>&nbsp;
					<button type=submit name=Motor_move_dis value=14 style='width:180px;height:40px;'>向下 1 mm</button>&emsp;
					
					<button type=submit name=Motor_move_dis value=15 style='width:180px;height:40px;'>100 mm 向上</button>&nbsp;
					<button type=submit name=Motor_move_dis value=16 style='width:180px;height:40px;'>10 mm 向上</button>&nbsp;
					<button type=submit name=Motor_move_dis value=17 style='width:180px;height:40px;'>1 mm 向上</button>
				</th>
			</tr>

			<tr>
				<th>步進馬達設定</th>
				<th>
					<input type=radio name=Motor value=5> W 軸
					<input type=radio name=Motor value=7> X 軸
					<input type=radio name=Motor value=9> Y 軸
					<input type=radio name=Motor value=11> Z 軸
					&nbsp;&nbsp;
					<input type=text name=Set_Motor_dis size=8>&nbsp;mm
					&nbsp;&emsp;
					<button type=submit name=Motor_move_dir value=0 style='width:140px;height:40px;'>前進/向下</button>&nbsp;&emsp;
					<button type=submit name=Motor_move_dir value=1 style='width:140px;height:40px;'>後退/向上</button>
				</th>

			</tr>
			<tr>
				<th>電磁鐵設定</th>
				<th>
					<button type=submit name=EMT value=0 style='width:140px;height:40px;'>吸住</button>&nbsp;&emsp;
					<button type=submit name=EMT value=1 style='width:140px;height:40px;'>放下</button>
				</th>

			</tr>
			</form>
			</table><br>";
		
		echo "<div  id='table'></div><br></table><br>

		<table border=3 width=80% cellspacing=2 cellpadding=10 bordercolor=Blue>
		<tr>
			<th><a href='./sys.php'>返回系統管理功能</a></th>
		</tr>
		</table>";
	}
	else
	{
		echo "<center><table border=3 width=80% cellspacing=2 cellpadding=10 bordercolor=Blue>
			<form action='./Crane.php' method='POST'>

			<tr>
				<th>
					選擇設備 :  &nbsp;&emsp;<select name='operator_ch' style='height : 30; width: 60;'>";
					$sql = "select NAME from Wi_SUN_IP where Category = '2' ";
					$result = mysqli_query($con, $sql);

					$i=0;
					while(list($operator_name[$i]) = mysqli_fetch_row($result))
					$i ++;
					
					for($j=0; $j< $i; $j++)
					{
						echo "<option value='$operator_name[$j]'>$operator_name[$j]</option>";
					}
					mysqli_free_result($result);
		echo "<select>&nbsp;&emsp;<input type='submit' Name='ok' value='提交' />
				</th>
				
			</tr></table><br>
			<center><table border=3 width=80% cellspacing=2 cellpadding=10 bordercolor=Blue>
			<tr>
			<th><a href='./sys.php'>返回系統管理功能</a>
			</th>
			</tr></table>";
		echo "</form></center>";
	}
	
	?>
	</center>
	
</body>
</html>