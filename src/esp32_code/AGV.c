#include <esp_task_wdt.h>
#include <EEPROM.h>		
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>

#define	SIZE		200
#define	LINELIMIT	300
#define PORTNO		5678	
#define	TIMEOUT		2000
#define	TRUE		1
#define FALSE		0
#define SLAVE_ID	1
typedef word	WORD;
typedef	byte	BYTE;

// 紅外線循跡模組設定
#define	tr_right	0
#define	tr_middle	1
#define	tr_left		2
byte tr[3] = {34,35,32};

// 超聲波模組設定
#define	echo		0
#define	trig		1
#define STOP_DISTANCE	20
long duration; 
int distance; 
byte echo_trig[2] = {25,33};

// 4 顆步進馬達驅動器設定
#define	motor_right	0
#define	motor_left	1
#define	dir			0
#define	step		1
#define	enable		2
#define Motor_go	1
#define Motor_stop	2
byte motor[2][3]={{26,27,14},{4,13,15}};
bool dirstatue[2] = { 0, 0};

// 計時器中斷設定
hw_timer_t * timer = NULL;
bool motor_timer_flag = 1;

//暫存器
#define MAX_ADDR		20			// 最大暫存器數量
#define	OP_code_ADDR		0		// 運算碼
#define CarStatus_ADDR 		1		// AGV 設備狀態
#define CargoStatus_ADDR 	2		// 貨物狀態	
#define CarWhere_ADDR		3		// AGV 設備位置
#define MotorStatus_ADDR	4		// 馬達狀態
#define Initialstep_ADDR      	10      	// 儲存起始站點 RFID 標籤 
#define Takestep_ADDR     	12      		// 儲存工作站點 RFID 標籤  
#define Cranestep_ADDR      	14      	// 儲存倉儲站點 RFID 標籤  

// AGV 設備狀態
#define	Idle				0	// 閒置
#define	Work_goTake			1	// 正在前往工作站點
#define	AR_Takestep			2	// 抵達工作站點
#define	Work_goCrane			3	// 正在前往倉儲站點
#define	Wait_cargo_leave		4	// 等待貨物離開
#define	Work_goBack				5	// 正在前往起始站點
#define	AR_Initialstep			6	// 抵達起始站點
#define	Pause					7	// 暫停
#define	Acd						8	// 意外
#define	Wait_Connect			9	// 等待連線

// 貨物狀態
#define	Cargo_n			0	// 沒貨物
#define	Cargo_y			1	// 有貨物

// AGV 設備位置
#define Initial_step			0	// 起始站點
#define En_Route_Takestep		1	// 正在前往工作站點
#define Take_step				2	// 工作站點
#define En_Route_Cranestep		3	// 正在前往倉儲站點
#define Crane_step				4	// 倉儲站點
#define En_Route_Initialstep	5	// 正在前往起始站點

// 運算碼
#define op_Nop				0	// 不動作
#define op_workGo			1	// 開始自動作業
#define op_workBcak			2	// 停止自動作業並返回起始站點
#define op_workPause		3	// 暫停自動作業
#define op_workContinue		4	// 繼續自動作業
#define op_workStop			5	// 停止自動作業
#define op_goHome			6	// 前往起始站點
#define op_goStep			7	// 前往工作站點
#define op_goCrane			8 	// 前往倉儲站點
#define op_Cargo_leave		9	// 貨物已離開
#define op_Reset			10	// 設備重置
#define op_Cnn				11	// VC7300 完成組網

//LEDs 燈設定
#define RED					2	
#define GREEN				1	
#define BLUE				0	
byte led[3]={2, 12, 21}; 
byte twk = 0;

//RFID 模組設定
#define RST_PIN      22           	// 讀卡機的重置腳位
#define SS_PIN       5           	// 晶片選擇腳位
MFRC522 mfrc522(SS_PIN, RST_PIN);   // 建立MFRC522物件

 /*
EEPROM 設定
EEPROM： 注意格式為 WORD, 一次使用兩格子(2 bytes), 因此需先宣告
EEPROM： 注意使用時須乘以 2 
*/
#define EEPROM_siteInitialstep		10	// 起始站點 RFID 標籤
#define EEPROM_siteTake				14	// 工作站點 RFID 標籤
#define EEPROM_siteCrane			18	// 倉儲站點 RFID 標籤
#define EEPROM_CarStatus			22	// AGV 設備狀態
#define EEPROM_Cargo				24	// 貨物狀態
#define EEPROM_CarWhere				26	// AGV 設備位置
#define EEPROM_Workflag				28	// 自動作業斷電紀錄
#define EEPROM_MAX					50	// 最大 EEPROM 數量

// 軟體中斷時間
unsigned long ultrasound_timer; // 超聲波模組
unsigned long led_timer;		// LED 燈
unsigned long cnn_timer;		// 連線時間

// 雙核心旗標設定
TaskHandle_t 	Task1;
bool	Work_flag = false;
bool	Pause_flag = false;
bool	gohome_flag = false;
bool	gostep_flag = false;
bool	gocrane_flag = false;
bool	Cnn_flag = false;

// 前往站點類別 RFID 判斷 
byte	RFID_flag[3] = {0, 1, 2}; 
#define gohome		0
#define gostep		1
#define gocrane		2



// VC7300 訊息設定
char	cn_send[] = "AT+USOCKSEND=0,5678,\"fe80::fdff:ffff:f45a:e22\",4,\"FFFF\"";
int		udp_index;			// 執行 AT+USOCKREG 產生的 udp socket index
typedef struct
{
	WORD	fd_value;
	WORD	remote_port;
	char	remote_address[50];
	WORD	length;				// 必須是 4 的倍數
	char	data[LINELIMIT];
} UDP_PACKET;

// Modbus 設定
char		input_buf[LINELIMIT];
WORD		input_status, input_index;
unsigned long	input_timer;
BYTE		output_buf[LINELIMIT];
WORD		hold_registers[SIZE];
WORD		hold_registerstmp;
typedef struct
{
	WORD	transaction_id;
	WORD	protocol_id;
	WORD	length;		// bytes of unit_id + function_code + ...
	BYTE	unit_id;
	BYTE	function_code;
	WORD	starting_address;
	WORD	no_of_points;
	BYTE	byte_count;
	WORD	reg_values[SIZE];
} MOD_PACKET;

// Exception Code for ModBus
#define MB_NOT_OWN		01			// Not message owner

UDP_PACKET	recv_udp;
MOD_PACKET	recv_modbus;

// utility
BYTE to_binary(BYTE firstbyte, BYTE secondbyte)
{
	BYTE	po = 0;
	
	if (firstbyte <= '9')
		po = (firstbyte - '0');
	else	po = (firstbyte - 'A') + 10;
	
	po = po << 4;
		
	if (secondbyte <= '9')
		po |= (secondbyte - '0');
	else	po |= (secondbyte - 'A') + 10;
	
	return po;
}

// utility
void to_hexascii(BYTE tmp[2], BYTE data)
{
	BYTE	p;
	
	p = (data & 0XF0) >> 4;
	if (p <= 9)
		tmp[0] = '0' + p;
	else	tmp[0] = 'A' + p - 10;
	
	p = data & 0X0F;
	if (p <= 9)
		tmp[1] = '0' + p;
	else	tmp[1] = 'A' + p - 10;
}

// 傳送回應訊框
void send_response_packet(BYTE *job, WORD length, UDP_PACKET *udp)
{
	char	output[LINELIMIT], ttt[10];
	BYTE	tmp[2];
	
	char	data[LINELIMIT];
	int	i, len;
	
	len = 0;
	for (i = 0; i < length; i ++)
	{
		to_hexascii(tmp, job[i]);
		data[len ++] = char(tmp[0]);
		data[len ++] = char(tmp[1]);
	}
	
	while (len % 4 != 0)
	{
		data[len] = '0';
		len ++;
	}
	data[len] = '\0';
	
	sprintf(output, "AT+USOCKSEND=%d,%d,\"%s\",%d,\"%s\"",
			udp_index,
			udp->remote_port,
			udp->remote_address,
			len,
			data);
	
	/*
	strcpy(output, "AT+USOCKSEND=");
	itoa(udp_index, ttt, 10);
	strcat(output, ttt);
	strcat(output, ",");
	
	itoa(udp->remote_port, ttt, 10);
	strcat(output, ttt);
	strcat(output, ",\"");
	
	strcat(output, udp->remote_address);
	strcat(output, "\",");
	
	itoa(len, ttt, 10);
	strcat(output, ttt);
	
	strcat(output, ",\"");
	strcat(output, data);
	strcat(output, "\"");
	*/
	
	Serial2.println(output);
	
	while (! Serial2.available()) ;		// 等候 VC7300 回應

	while (! Serial2.find("OK"))
	{
		Serial2.println(output);
		delay(100);
	}
}

// 產生 exception response frame
void gen_exception_response_packet(BYTE *msg, WORD *size, BYTE except_code, MOD_PACKET *input)
{
	BYTE	j = 0;

	msg[j ++] = input->transaction_id / 256;
	msg[j ++] = input->transaction_id % 256;
	msg[j ++] = input->protocol_id / 256;
	msg[j ++] = input->protocol_id % 256;
	msg[j ++] = input->length / 256;
	msg[j ++] = input->length % 256;
	msg[j ++] = input->unit_id;
	msg[j ++] = input->function_code | 0X80;
	msg[j ++] = except_code;
	*size = j;
}

// 產生 function code 03 回應: read Holding Registers
void gen_03_response_packet(BYTE *msg, WORD *size, MOD_PACKET *input)
{
	BYTE	i, j;
	BYTE	bytecount = 0;
	WORD	data;
	
	if (input->starting_address + input->no_of_points > SIZE)
	{
		gen_exception_response_packet(msg, size, 0X02, input);
		return;
	}
	
	j = 0;
	
	msg[j ++] = input->transaction_id / 256;
	msg[j ++] = input->transaction_id % 256;
	msg[j ++] = input->protocol_id / 256;
	msg[j ++] = input->protocol_id % 256;
	
	data = 3 + input->no_of_points * 2;	// for length
	msg[j ++] = data / 256;
	msg[j ++] = data % 256;
	
	msg[j ++] = input->unit_id;
	
	if (input->unit_id != SLAVE_ID)
	{
		gen_exception_response_packet(msg, size, MB_NOT_OWN, input);
		return;
	}
	
	msg[j ++] = input->function_code;
	
	bytecount = input->no_of_points * 2;
	msg[j ++] = (BYTE) (bytecount);
	
	for (i = 0; i < input->no_of_points; i ++)
	{
		data = hold_registers[input->starting_address + i];
		msg[j ++] = data / 256;
		msg[j ++] = data % 256;
	}
	
	*size = j;
}

// 產生 function code 10 回應:  Preset Multiple Registers
void gen_10_response_packet(BYTE *msg, WORD *size, MOD_PACKET *input)
{
	BYTE	i, j;
	BYTE	bytecount = 0;
	WORD	data;
	
	if (input->starting_address + input->no_of_points > SIZE)
	{
		gen_exception_response_packet(msg, size, 0X02, input);
		return;
	}
	
	j = 0;
	
	msg[j ++] = input->transaction_id / 256;
	msg[j ++] = input->transaction_id % 256;
	msg[j ++] = input->protocol_id / 256;
	msg[j ++] = input->protocol_id % 256;
	
	data = 6;
	msg[j ++] = data / 256;		// for length
	msg[j ++] = data % 256;
	msg[j ++] = input->unit_id;
/*
	if (input->unit_id != SLAVE_ID)
	{
		gen_exception_response_packet(msg, size, MB_NOT_OWN, input);
		return;
	}
*/
	msg[j ++] = input->function_code;
	
	msg[j ++] = (BYTE) (input->starting_address / 256);
	msg[j ++] = (BYTE) (input->starting_address % 256);
	
	msg[j ++] = (BYTE) (input->no_of_points / 256);
	msg[j ++] = (BYTE) (input->no_of_points % 256);
	
	*size = j;
}

// 讀取 Serial2 輸入 (from VC7300)
void read_input()
{
	char	ch;
	ch = Serial2.read();

	switch(input_status)
	{
		case 0:	if (ch == '+')	// leading character
			{
				input_status = 1;
				input_index = 0;
				
				input_buf[input_index] = ch;
				input_index ++;
				
				input_timer = millis();
			}
			break;
			
		case 1: if ((ch >= '0' && ch <= '9') || (ch >= 'A' && ch <= 'F') || (ch >= 'a' && ch <= 'f')
				|| (ch == '\"')	|| (ch == ',') || (ch == ' ') || (ch == ':'))
			{
				if (ch >= 'a' && ch <= 'f')
					ch = ch - 32;
				
				input_status = 1;
				input_buf[input_index] = ch;
				input_index ++;
				
				if (input_index >= LINELIMIT)	// input buffer overrun
				{
					input_status = 0;
					input_index = 0;
				}
				
				input_timer = millis();
			}
			else if (ch == '\n')
			{
				input_status = 2;
				input_buf[input_index] = '\0';
				input_timer = millis();
			}
			break;
	}
}

// 檢查 VC7300 輸入資料並轉換成 FRAME 結構
int parse_input(char *input, UDP_PACKET *udp, MOD_PACKET *mod)
{
	char	*p, ttt[100];
	int	i, j;
	BYTE	data;
	WORD	wt;
	
	p = input;
	
	if (*p == '\0')
		return FALSE;
	
	i = 0;			// 抓取 "+USOCKRECV:"
	while (*p != ' ')
	{
		ttt[i] = *p;
		p ++;
		i ++;
		if (*p == '\0')
			return FALSE;
	}
	ttt[i] = '\0';
	p ++;
	
	//if (strcmp("+USOCKRECV:", ttt) != 0)
		//return FALSE;
	
	p ++;	// skip ' '
	
	i = 0;			// 抓取 fd_value
	while (*p != ',')
	{
		ttt[i] = *p;
		
		i ++;
		p ++;
		if (*p == '\0')
			return FALSE;
	}
	ttt[i] = '\0';
	p ++;
	
	udp->fd_value = atoi(ttt);
	
	i = 0;			// 抓取 remote_port
	while (*p != ',')
	{
		ttt[i] = *p;
		
		i ++;
		p ++;
		if (*p == '\0')
			return FALSE;
	}
	ttt[i] = '\0';
	p ++;
	
	udp->remote_port = atoi(ttt);
	
	while (*p != '\"')
	{
		p ++;
		if (*p == '\0')
			return FALSE;
	}
	p ++;
	
	i = 0;			// 抓取 remote_address
	while (*p != '\"')
	{
		ttt[i] = *p;
		
		p ++;
		i ++;
		if (*p == '\0')
			return FALSE;
	}
	ttt[i] = '\0';
	p ++;
	
	strcpy(udp->remote_address, ttt);
	
	while (*p != ',')	// 跳過 ','
	{
		p ++;
		if (*p == '\0')
			return FALSE;
	}
	p ++;
	
	i = 0;			// 抓取 length
	while (*p != ',')
	{
		ttt[i] = *p;
		
		i ++;
		p ++;
		if (*p == '\0')
			return FALSE;
	}
	ttt[i] = '\0';
	p ++;
	udp->length = atoi(ttt);
	
	while (*p != '\"')	// 尋找下一個 "
	{
		p ++;
		if (*p == '\0')
			return FALSE;
	}
	p ++;
	
	i = 0;		// 抓取 data
	while (*p != '\"')
	{
		ttt[i] = *p;
		
		p ++;
		i ++;
		if (*p == '\0')
			return FALSE;
	}
	ttt[i] = '\0';
	p ++;
	
	strcpy(udp->data, ttt);
	
	// parse MODBUS
	if (i < 10)
		return FALSE;
	
	p = udp->data;
	
	wt = to_binary(*p, *(p+1));
	p = p + 2;
	wt = wt * 256 + to_binary(*p, *(p+1));
	p = p + 2;
	mod->transaction_id = wt;
	
	wt = to_binary(*p, *(p+1));
	p = p + 2;
	wt = wt * 256 + to_binary(*p, *(p+1));
	p = p + 2;
	mod->protocol_id = wt;
	
	wt = to_binary(*p, *(p+1));
	p = p + 2;
	wt = wt * 256 + to_binary(*p, *(p+1));
	p = p + 2;
	mod->length = wt;
	
	mod->unit_id = to_binary(*p, *(p+1));
	p = p + 2;
	
	mod->function_code = to_binary(*p, *(p+1));
	p = p + 2;
	
	wt = to_binary(*p, *(p+1));
	p = p + 2;
	wt = wt * 256 + to_binary(*p, *(p+1));
	p = p + 2;
	mod->starting_address = wt;
	
	wt = to_binary(*p, *(p+1));
	p = p + 2;
	wt = wt * 256 + to_binary(*p, *(p+1));
	p = p + 2;
	mod->no_of_points = wt;
	
	switch (mod->function_code)
	{
		case 0X03: break;
		
		case 0X10:
			// get byte count, 1 bytes
			mod->byte_count = to_binary(*p, *(p+1));
			p = p + 2;
			
			if (mod->byte_count <= 0)
				return FALSE;
			
			if (i - 24 < mod->byte_count)
				return FALSE;
			
			// get registers' values in WORD
			for (i = 0; i < mod->no_of_points; i++)
			{
				wt = to_binary(*p, *(p+1));
				p = p + 2;
				wt = wt * 256 + to_binary(*p, *(p+1));
				p = p + 2;
				mod->reg_values[i] = wt;
			}
			
			break;
		default:
			return FALSE;
	}
	
	return TRUE;
}

// 初始化暫存器
void Initial_HR(void)								
{
	for (int i = 0; i < MAX_ADDR; i ++)
		hold_registers[i] = 0;
}

// 重置設定
void Reset_ESP32(void)
{
	digitalWrite(motor[motor_right][enable], HIGH);
	digitalWrite(motor[motor_left][enable], HIGH);
	Work_flag = false;
	Pause_flag = false;
	gohome_flag = false;
	gostep_flag = false;
	gocrane_flag = false;
	Motor_Status(Motor_stop);
	for (int i = 0; i < MAX_ADDR; i ++)
		hold_registers[i] = 0;
	
	for (int i = 0; i < EEPROM_MAX; i ++)
		EEPROM.write(i, 0);
	EEPROM.commit();
}

// 初始化設定
void Initial_set(void)
{
	Initial_HR();
	Serial.begin(115200);	 
	Serial2.begin(115200);	// from VC7300
	
	// 設定 ESP32 接腳屬性
	BYTE i,j;
	for(i = 0;i < 2;i++)
	{
		for(j = 0;j < 3;j++)
		{
			pinMode(motor[i][j],OUTPUT);
			pinMode(tr[j],INPUT);
			pinMode(led[j],OUTPUT);
		}
		
	}
	pinMode(echo_trig[echo], INPUT);
	pinMode(echo_trig[trig], OUTPUT);

	// 在核心 0 啟動 Task1
	xTaskCreatePinnedToCore
	(
		Task1_senddata,
		"Task1",
		10000,
		NULL,
		0,
		&Task1,
		0
	);
	
	
	EEPROM.begin(EEPROM_MAX);	//設定 EEPROM 
	esp_task_wdt_init(10, false);	// 設定看門狗並關閉自動重新啟動

	// 設定 RFID 模組
	delay(100);
	SPI.begin();    // 開啟 SPI 界面
	delay(50);
	mfrc522.PCD_Init(); // 初始化MFRC522讀卡機模組
	delay(100);
	
	// 關閉步進馬達電流
	digitalWrite(motor[motor_left][enable], HIGH);
	digitalWrite(motor[motor_right][enable], HIGH);
	hold_registers[MotorStatus_ADDR] = Motor_stop;
	
	// 記錄軟體時間
	ultrasound_timer = millis();
	led_timer = ultrasound_timer;
	cnn_timer = ultrasound_timer;
	
	// 初始化全域變數
	motor_timer_flag = 0;
	input_status = 0;
	input_index = 0;
	input_timer = 0;
	Cnn_flag = 0;
	
	// 開機等待 VC7300 組網
	hold_registers[CarStatus_ADDR] = Wait_Connect;
}

// 初始化 VC7300
void Initial_WI_SUN()
{
	char	ss[LINELIMIT], ttt[50], ppp[50];
	int	i, j;
	delay(3000);

	// 關閉 VC7300 重複回傳
	strcpy(ss, "AT+ECHO=0");
	Serial2.println(ss);
	delay(1000);
	
	// 註冊 socket
	strcpy(ss, "AT+USOCKREG");		// udp_index
	Serial2.println(ss);
	udp_index = 0;
	delay(1000);
	
	// 綁定本地端連接埠
	sprintf(ss, "%s%d,%d", "AT+USOCKBIND=", udp_index, PORTNO);
	Serial2.println(ss);
}

// 讀取 VC7300 輸入資料
void Wi_SUN_RECEIVE()
{
	WORD	length, i;
	
	if (Serial2.available())
		read_input();
	// 處理 VC7300 輸入資料
	if (input_status == 2)
	{
		if (parse_input(input_buf, &recv_udp, &recv_modbus) == TRUE)
		{
			switch (recv_modbus.function_code)
			{
				case 0X03:
					gen_03_response_packet(output_buf, &length, &recv_modbus);
					break;
						
				case 0X10:	
					gen_10_response_packet(output_buf, &length, &recv_modbus);	
									
					break;
			}
			
			send_response_packet(output_buf, length, &recv_udp);
			do_action();
		}		
		// reset input buffer index and status
		input_index = 0;
		input_status = 0;
	}
	
	// input timeout, reset input buffer indexpp and status
	if (input_status != 0)
	{
		if (millis() - input_timer > TIMEOUT)
		{
			input_index = 0;
			input_status = 0;
			input_timer = millis();
		}
	}
}

// 步進馬達定時中斷設定
void IRAM_ATTR onTimer()
{
	if(hold_registers[MotorStatus_ADDR] == Motor_go)
	{	
		BYTE   path = 0;
		path = ((digitalRead(tr[tr_left])) * 2 + digitalRead(tr[tr_middle])) * 2 + digitalRead(tr[tr_right]); 
		switch(path)
		{
			case 0:			// 直線
				digitalWrite(motor[motor_right][dir], 1);
				digitalWrite(motor[motor_left][dir], 1);	
				dirstatue[0] = 1;
				dirstatue[1] = 1;	
				break;					
			case 1:			// 左轉 
				digitalWrite(motor[motor_right][dir], 1);
				digitalWrite(motor[motor_left][dir], 1);	
				dirstatue[0] = 1;
				dirstatue[1] = 0;				
				break;
			case 2:			//error
				digitalWrite(motor[motor_right][dir], 1);
				digitalWrite(motor[motor_left][dir], 0);	
				dirstatue[0] = 1;
				dirstatue[1] = 1;	
				break;	
			case 3:			// 左轉
				digitalWrite(motor[motor_right][dir], 1);
				digitalWrite(motor[motor_left][dir], 0);	
				dirstatue[0] = 1;
				dirstatue[1] = 0;	
				//Serial.println(" 左彎	");
				break;	
			case 4:			// 右轉
				digitalWrite(motor[motor_right][dir], 1);
				digitalWrite(motor[motor_left][dir], 1);	
				dirstatue[0] = 0;
				dirstatue[1] = 1;	
				break;		
			case 5:			// 直線
				digitalWrite(motor[motor_right][dir], 1);
				digitalWrite(motor[motor_left][dir], 0);
				dirstatue[0] = 1;
				dirstatue[1] = 1;	
				break;	
			case 6:			// 右轉
				digitalWrite(motor[motor_right][dir], 0);
				digitalWrite(motor[motor_left][dir], 1);	
				dirstatue[0] = 0;
				dirstatue[1] = 1;	
				break;	
			case 7:			// 倒退
				digitalWrite(motor[motor_right][dir], 0);
				digitalWrite(motor[motor_left][dir], 1);	
				dirstatue[0] = 1;
				dirstatue[1] = 1;	
				break;			
		}
		// 步進馬達通電
		digitalWrite(motor[motor_right][enable], LOW);
		digitalWrite(motor[motor_left][enable], LOW);
		
		// 驅動步進馬達
		if (motor_timer_flag == 1)
		{
			digitalWrite(motor[motor_right][step], dirstatue[0]);
			digitalWrite(motor[motor_left][step], dirstatue[1]);
			motor_timer_flag = 0;
		}
		else
		{	
			digitalWrite(motor[motor_right][step], LOW);
			digitalWrite(motor[motor_left][step], LOW);
			motor_timer_flag = 1;
		} 
	}
}

// 執行動作
void do_action()
{
	WORD	i,j;	
	WORD EEPROM_GET;
	if(recv_modbus.function_code == 0X10)
	{
		
		if(hold_registers[CarStatus_ADDR] == Wait_Connect)	// 等待組網
		{
			if((recv_modbus.starting_address == OP_code_ADDR) && recv_modbus.reg_values[0] == 11)
			{
				Cnn_flag = 1;
				hold_registers[CarStatus_ADDR] = Idle;
			}
		}
		else
		{
			//初始化 mfrc522
			mfrc522.PCD_Init();
			switch(recv_modbus.starting_address)
			{
				case OP_code_ADDR:
					switch(recv_modbus.reg_values[0])
					{
						case op_Nop: break;
						// 自動作業五項作業
						case op_workGo:
							for(j = 0; j < 2; j++)
							{
								if((hold_registers[Initialstep_ADDR + j] == 0) 
									|| (hold_registers[Takestep_ADDR + j] == 0)
									|| (hold_registers[Cranestep_ADDR + j] == 0))
								{
									hold_registers[CarStatus_ADDR] = Idle;
								}
								else
								{
									hold_registers[CarStatus_ADDR] = Work_goTake;
									Work_flag = 1;
									EEPROM.write(EEPROM_Workflag, Work_flag);
								}
							}
							EEPROM.commit();
							break;
						case op_workBcak:
							hold_registers[CarStatus_ADDR] = Work_goBack;
							hold_registers[CargoStatus_ADDR] = Cargo_n;
							hold_registers[CarWhere_ADDR] = En_Route_Initialstep;
							EEPROM.write(EEPROM_CarStatus, Work_goBack);
							EEPROM.write(EEPROM_Cargo, Cargo_n);
							EEPROM.write(EEPROM_CarWhere, En_Route_Initialstep);
							EEPROM.commit();
							break;
						case op_workPause:
							Pause_flag = 1;
							break;
						case op_workContinue:
							Pause_flag = 0;
							break;
						case op_workStop:
							Work_flag = 0;
							Register_with_EEPROM_Save(Work_goBack, En_Route_Initialstep);
							gohome_flag = 1;
							break;
						// 手動作業三項作業
						case op_goHome:
							if((hold_registers[CarStatus_ADDR] == Idle) 
								&&(hold_registers[Initialstep_ADDR] != 0)
								&&(hold_registers[Initialstep_ADDR + 1] != 0))
								{
									if(hold_registers[CarWhere_ADDR] != Initial_step)
									{
										Register_with_EEPROM_Save(Work_goBack, En_Route_Initialstep);
										gohome_flag = 1;
									}
								}
							break;
						case op_goStep:
							if((hold_registers[CarStatus_ADDR] == Idle) 
								&&(hold_registers[Takestep_ADDR] != 0)
								&&(hold_registers[Takestep_ADDR + 1] != 0))
								{
									if(hold_registers[CarWhere_ADDR] != Take_step)
									{
										hold_registers[CarStatus_ADDR] =  Work_goTake;
										hold_registers[CarWhere_ADDR] = En_Route_Takestep;
										hold_registers[CargoStatus_ADDR] = Cargo_y;
										EEPROM.write(EEPROM_Cargo, Cargo_y);
										EEPROM.write(EEPROM_CarStatus, Work_goTake);
										EEPROM.write(EEPROM_CarWhere, En_Route_Takestep);
										EEPROM.commit();
										gostep_flag = 1;
									}
								}
							break;
						case op_goCrane:
							if((hold_registers[CarStatus_ADDR] == Idle) 
								&& (hold_registers[Cranestep_ADDR] != 0)
								&& (hold_registers[Cranestep_ADDR + 1] != 0))
								{
									if(hold_registers[CarWhere_ADDR] != Crane_step)
									{
										Register_with_EEPROM_Save(Work_goCrane, En_Route_Cranestep);
										gocrane_flag = 1;
									}
								}
							break;
						// 貨物離開
						case op_Cargo_leave:
							if(hold_registers[CarStatus_ADDR] == Idle)
							{
								hold_registers[CargoStatus_ADDR] = Cargo_n;
								EEPROM.write(EEPROM_Cargo, Cargo_n);
								EEPROM.commit();
								gohome_flag = 1;
							}
							break;
						// ESP32 重置
						case op_Reset:
								Reset_ESP32();
							break;
						
					}
					break;
				case Initialstep_ADDR:	//寫入起始站點 RFID 資料
					for (i = 0; i < recv_modbus.no_of_points; i ++)
					{
						hold_registers[recv_modbus.starting_address + i] = recv_modbus.reg_values[i];
					}
					
					EEPROM.write(EEPROM_siteInitialstep, hold_registers[Initialstep_ADDR] / 256);
					EEPROM.write(EEPROM_siteInitialstep + 1, hold_registers[Initialstep_ADDR] % 256);
					EEPROM.write(EEPROM_siteInitialstep + 2, hold_registers[Initialstep_ADDR + 1] / 256);
					EEPROM.write(EEPROM_siteInitialstep + 3, hold_registers[Initialstep_ADDR + 1] % 256);
					EEPROM.commit();
					break;
				case Takestep_ADDR:	//寫入工作站點 RFID 資料
					for (i = 0; i < recv_modbus.no_of_points; i ++)
					{
						hold_registers[recv_modbus.starting_address + i] = recv_modbus.reg_values[i];
					}
			
					EEPROM.write(EEPROM_siteTake, hold_registers[Takestep_ADDR] / 256 );
					EEPROM.write(EEPROM_siteTake + 1, hold_registers[Takestep_ADDR] % 256);
					EEPROM.write(EEPROM_siteTake + 2, hold_registers[Takestep_ADDR + 1] / 256);
					EEPROM.write(EEPROM_siteTake + 3, hold_registers[Takestep_ADDR + 1] % 256);
					EEPROM.commit();
					break;
				case Cranestep_ADDR: //寫入倉儲站點 RFID 資料
					for (i = 0; i < recv_modbus.no_of_points; i ++)
					{
						hold_registers[recv_modbus.starting_address + i] = recv_modbus.reg_values[i];
					}
					EEPROM.write(EEPROM_siteCrane, hold_registers[Cranestep_ADDR] / 256);
					EEPROM.write(EEPROM_siteCrane + 1, hold_registers[Cranestep_ADDR] % 256);
					EEPROM.write(EEPROM_siteCrane + 2, hold_registers[Cranestep_ADDR + 1] / 256);
					EEPROM.write(EEPROM_siteCrane + 3, hold_registers[Cranestep_ADDR + 1] % 256);
					EEPROM.commit();
					break;	
			}
		}			
	}
}

// 取得 ESP32 FLASH 資料
void	gainEEPROM()
{
	byte  EEPROM_GETData;
	BYTE  i;
	
	//取得倉儲站點 RFID 標籤資料
	for(i = 0; i < 4;i++)
	{
		EEPROM_GETData = 0;  //start clean
		EEPROM_GETData = EEPROM.read(EEPROM_siteCrane + i);
		switch(i)
		{
			case 0:
				hold_registers[Cranestep_ADDR] = hold_registers[Cranestep_ADDR] + EEPROM_GETData << 8;
			break;
			case 1:
				hold_registers[Cranestep_ADDR] = hold_registers[Cranestep_ADDR] + EEPROM_GETData;
			break;
			case 2:
				hold_registers[Cranestep_ADDR + 1] = hold_registers[Cranestep_ADDR + 1] + EEPROM_GETData << 8;
			break;
			case 3:
				hold_registers[Cranestep_ADDR + 1] = hold_registers[Cranestep_ADDR + 1] + EEPROM_GETData;
			break;
		}
		
	}

	//取得工作站點 RFID 標籤資料
	for(i = 0; i < 4;i++)
	{
		EEPROM_GETData = 0;  //start clean
		EEPROM_GETData = EEPROM.read(EEPROM_siteTake + i);
		switch(i)
		{
			case 0:
				hold_registers[Takestep_ADDR] = hold_registers[Takestep_ADDR] + EEPROM_GETData << 8;
			break;
			case 1:
				hold_registers[Takestep_ADDR] = hold_registers[Takestep_ADDR] + EEPROM_GETData;
			break;
			case 2:
				hold_registers[Takestep_ADDR + 1] = hold_registers[Takestep_ADDR + 1] + EEPROM_GETData << 8;
			break;
			case 3:
				hold_registers[Takestep_ADDR + 1] = hold_registers[Takestep_ADDR + 1] + EEPROM_GETData;
			break;
		}
		
	}
	
	//取得起始站點 RFID 標籤資料
	for(i = 0; i < 4;i++)
	{
		EEPROM_GETData = 0;  //start clean
		EEPROM_GETData = EEPROM.read(EEPROM_siteInitialstep + i);
		switch(i)
		{
			case 0:
				hold_registers[Initialstep_ADDR] = hold_registers[Initialstep_ADDR] + EEPROM_GETData << 8;
			break;
			case 1:
				hold_registers[Initialstep_ADDR] = hold_registers[Initialstep_ADDR] + EEPROM_GETData;
			break;
			case 2:
				hold_registers[Initialstep_ADDR + 1] = hold_registers[Initialstep_ADDR + 1] + EEPROM_GETData << 8;
			break;
			case 3:
				hold_registers[Initialstep_ADDR + 1] = hold_registers[Initialstep_ADDR + 1] + EEPROM_GETData;
			break;
		}
		
	}

	// 取得貨物狀態
	EEPROM_GETData = 0;  
	EEPROM_GETData = EEPROM.read(EEPROM_Cargo);   
	hold_registers[CargoStatus_ADDR] = EEPROM_GETData;

	// 取得 AGV 設備位置
	EEPROM_GETData = 0; 
	EEPROM_GETData = EEPROM.read(EEPROM_CarWhere);   
	hold_registers[CarWhere_ADDR] = EEPROM_GETData;
	
	// 取得 AGV 設備狀態
	EEPROM_GETData = 0;  
	EEPROM_GETData = EEPROM.read(EEPROM_CarStatus);   
	hold_registers[CarStatus_ADDR] = EEPROM_GETData;
	
	// 取得作業狀態
	EEPROM_GETData = 0;  
	EEPROM_GETData = EEPROM.read(EEPROM_Workflag);   
	Work_flag = EEPROM_GETData;
}

// 偵測站點(自動作業使用)
void	scanningSite()
{
	unsigned long newID, oldID;
	BYTE i;
	if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) 
	{   
		byte *id = mfrc522.uid.uidByte;   // 取得卡片的UID
		byte idSize = mfrc522.uid.size;   // 取得UID的長度

		  
		for (byte i = 0; i < idSize; i ++)
		{
			newID = newID << 8;
			newID = newID | id[i];
		}
		mfrc522.PICC_HaltA();

		switch(hold_registers[CarStatus_ADDR])
		{
			case Work_goTake:
				oldID = (unsigned long) hold_registers[Takestep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Takestep_ADDR + 1];
				break;
			case Work_goCrane:
				oldID = (unsigned long) hold_registers[Cranestep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Cranestep_ADDR + 1];
				break;
			case Work_goBack:
				oldID = (unsigned long) hold_registers[Initialstep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Initialstep_ADDR + 1];
				break;
		}
		if(oldID == newID)
		{
			switch(hold_registers[CarStatus_ADDR])
			{
				case Work_goTake:
					hold_registers[CarStatus_ADDR] = AR_Takestep;
					digitalWrite(motor[motor_left][enable], HIGH);
					digitalWrite(motor[motor_right][enable], HIGH);
					break;
				case Work_goCrane:
					hold_registers[CarStatus_ADDR] = Wait_cargo_leave;
					break;
				case Work_goBack:
					digitalWrite(motor[motor_left][enable], HIGH);
					digitalWrite(motor[motor_right][enable], HIGH);
					hold_registers[CarStatus_ADDR] = AR_Initialstep;
					break;
			}
		}
		
		Serial.print("newID :");
		Serial.print(newID);
		Serial.print("  ,oldID :");
		Serial.println(oldID);
		
		delay(100);
	}
}

// 偵測站點(手動作業使用)
void ChooseSite(byte Core_flag)
{
	unsigned long newID, oldID;
	BYTE i;
	if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) 
	{ 
		byte *id = mfrc522.uid.uidByte;   // 取得卡片的UID
		byte idSize = mfrc522.uid.size;   // 取得UID的長度

		for (byte i = 0; i < idSize; i ++)
		{
			newID = newID << 8;
			newID = newID | id[i];
		}

		mfrc522.PICC_HaltA();

		switch(Core_flag)
		{
			case gohome:
				oldID = (unsigned long) hold_registers[Initialstep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Initialstep_ADDR + 1];
				
				if(hold_registers[CarWhere_ADDR] != En_Route_Initialstep)
				{
					hold_registers[CarStatus_ADDR] = AR_Initialstep;
					hold_registers[CarWhere_ADDR] = En_Route_Initialstep;
					EEPROM.write(EEPROM_CarStatus, AR_Initialstep);
					EEPROM.write(EEPROM_CarWhere, En_Route_Initialstep);
					EEPROM.commit();
				}
				
				break;
			case gostep:
				oldID = (unsigned long) hold_registers[Takestep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Takestep_ADDR + 1];
				break;
			case gocrane:
				oldID = (unsigned long) hold_registers[Cranestep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Cranestep_ADDR + 1];
				break;
			default:
				hold_registers[CarStatus_ADDR] = Acd;
				break;
		}
		if(oldID == newID)
		{
			switch(Core_flag)
			{
				case gohome:
					oldID = (unsigned long) hold_registers[Initialstep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Initialstep_ADDR + 1];
					
					if(hold_registers[CarWhere_ADDR] != Initial_step)
					{
						hold_registers[CarStatus_ADDR] = Work_goBack;
						hold_registers[CarWhere_ADDR] = Initial_step;
						EEPROM.write(EEPROM_CarStatus, Work_goBack);
						EEPROM.write(EEPROM_CarWhere, Initial_step);
						EEPROM.commit();
						digitalWrite(motor[motor_left][enable], HIGH);
						digitalWrite(motor[motor_right][enable], HIGH);
					}
					gohome_flag = 0;
					break;
				case gostep:
					oldID = (unsigned long) hold_registers[Takestep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Takestep_ADDR + 1];
					
					if(hold_registers[CarWhere_ADDR] != Take_step)
					{
						hold_registers[CarStatus_ADDR] = Work_goTake;
						hold_registers[CarWhere_ADDR] = Take_step;
						EEPROM.write(EEPROM_CarStatus, Work_goTake);
						EEPROM.write(EEPROM_CarWhere, Take_step);
						EEPROM.commit();
						digitalWrite(motor[motor_left][enable], HIGH);
						digitalWrite(motor[motor_right][enable], HIGH);
					}
					gostep_flag = 0;
					break;
				case gocrane:
					oldID = (unsigned long) hold_registers[Cranestep_ADDR]  * 256 * 256 + (unsigned long) hold_registers[Cranestep_ADDR + 1];
					
					if(hold_registers[CarWhere_ADDR] != Crane_step)
					{
						hold_registers[CarStatus_ADDR] = Work_goCrane;
						hold_registers[CarWhere_ADDR] = Crane_step;
						EEPROM.write(EEPROM_CarStatus, Work_goCrane);
						EEPROM.write(EEPROM_CarWhere, Crane_step);
						EEPROM.commit();
					}
					gocrane_flag = 0;
					break;
			}
		}	
		/*
		Serial.print("newID :");
		Serial.print(newID);
		Serial.print("  ,oldID :");
		Serial.println(oldID);
		*/
		delay(100);
	}
}

// 馬達作業
void Motor_Status(byte m_status)
{
	
	if(m_status == Motor_go)
	{
		// Interrupt Motor data
		hold_registers[MotorStatus_ADDR] = Motor_go;
		digitalWrite(tr[trig], LOW);
		//Serial.print("Motor_gogo");
	}
	else if(m_status == Motor_stop)
	{
		hold_registers[MotorStatus_ADDR] = Motor_stop;
		
		//digitalWrite(motor[motor_right][enable], HIGH);
		//digitalWrite(motor[motor_left][enable], HIGH);
		//Serial.print("Motor_stop");
	}
}

// 資料寫入 ESP32 FLASH
void Register_with_EEPROM_Save(word st,word wh) //VARIABLE (CarStatus, CarWhere)
{
	hold_registers[CarStatus_ADDR] = st;
	hold_registers[CarWhere_ADDR] = wh;
	EEPROM.write(EEPROM_CarStatus, st);
	EEPROM.write(EEPROM_CarWhere, wh);
	EEPROM.commit();
}

// 核心 0 作業
void Task1_senddata(void * pvParameters)					
{
	for (;;) 
	{
		byte i,j;
		
		if((Work_flag) && (!Pause_flag))	//自動作業
		{
			scanningSite();
			switch(hold_registers[CarStatus_ADDR])
			{
				// 前往工作站點
				case Work_goTake:	
					Motor_Status(Motor_go);
					if(hold_registers[CarWhere_ADDR] != En_Route_Takestep)	//PROTECT EEPROM 
					{
						hold_registers[CarWhere_ADDR] = En_Route_Takestep;
						EEPROM.write(EEPROM_CarStatus, Work_goTake);
						EEPROM.write(EEPROM_CarWhere, En_Route_Takestep);
						EEPROM.commit();
					}
					break;
				// 抵達工作站點
				case AR_Takestep:
					Motor_Status(Motor_stop);
					hold_registers[CarWhere_ADDR] = Take_step;
					EEPROM.write(EEPROM_CarStatus, AR_Takestep);
					EEPROM.write(EEPROM_CarWhere, Take_step);
					EEPROM.commit();
					delay(3000);	//人工上貨
					
					hold_registers[CarWhere_ADDR] = En_Route_Cranestep;
					hold_registers[CarStatus_ADDR] = Work_goCrane;
					hold_registers[CargoStatus_ADDR] = Cargo_n;
					EEPROM.write(EEPROM_CarStatus, Work_goCrane);
					EEPROM.write(EEPROM_CarWhere, En_Route_Cranestep);
					EEPROM.write(EEPROM_Cargo, Cargo_y);
					EEPROM.commit();
					break;
				// 前往倉儲站點
				case Work_goCrane:
					Motor_Status(Motor_go);
					break;
				// 等待貨物離開
				case Wait_cargo_leave:	
					Motor_Status(Motor_stop);
					if(hold_registers[CarWhere_ADDR] != Crane_step)
					{
						hold_registers[CarWhere_ADDR] = Crane_step;
						EEPROM.write(EEPROM_CarStatus, Wait_cargo_leave);
						EEPROM.write(EEPROM_CarWhere, Crane_step);
						EEPROM.commit();
					}
					break;
				// 返回起始站點 
				case Work_goBack:
					Motor_Status(Motor_go);
					break;
				// 抵達起始站點
				case AR_Initialstep:
					Motor_Status(Motor_stop);
					Register_with_EEPROM_Save(Idle, Initial_step);
					Work_flag = false;
					EEPROM.write(EEPROM_Workflag, Work_flag);
					break;
				// 遇到障礙物
				case Acd:					
					break;
			}
			if(Pause_flag) 
			{
				Motor_Status(Motor_stop);
			}
		}
		else if(gohome_flag)	//前往起始站點作業
		{
			
			if(hold_registers[CarStatus_ADDR] != Acd)
			{	
				Motor_Status(Motor_go);
				ChooseSite(RFID_flag[gohome]);
			}
			if(!gohome_flag)
			{	
				Register_with_EEPROM_Save(Idle, Initial_step);
				Motor_Status(Motor_stop);
			}
			
		}
		else if(gocrane_flag)	//前往倉儲站點作業
		{
			if(hold_registers[CarStatus_ADDR] != Acd)
			{
				Motor_Status(Motor_go);
				ChooseSite(RFID_flag[gocrane]);
			}
			if(!gocrane_flag)
			{
				Register_with_EEPROM_Save(Idle, Crane_step);
				Motor_Status(Motor_stop);
			}
		}
		else if(gostep_flag)	//前往工作站點作業
		{
			if(hold_registers[CarStatus_ADDR] != Acd)
			{
				Motor_Status(Motor_go);
				ChooseSite(RFID_flag[gostep]);
			}
			if(!gostep_flag)
			{
				Register_with_EEPROM_Save(Idle, Take_step);
				Motor_Status(Motor_stop);
			}
		}
		else
		{
			delay(1);	//Task1休息，delay(1)不可省略
		}
	}
}

// LEDs 顯示作業
void led_task(byte i)
{
	switch(i)
	{
		case Wait_Connect: // GREEN twinkle
			if(twk == 0)
			{
				digitalWrite(led[BLUE], LOW);
				digitalWrite(led[RED], LOW);
				digitalWrite(led[GREEN], HIGH);
				twk = 1;
			}
			else
			{
				digitalWrite(led[BLUE], LOW);
				digitalWrite(led[RED], LOW);
				digitalWrite(led[GREEN], LOW);
				twk = 0;
			}
			break;
		case Idle:
		case AR_Initialstep: // GREEN 
			digitalWrite(led[BLUE], LOW);
			digitalWrite(led[RED], LOW);
			digitalWrite(led[GREEN], HIGH);
			break;
		case AR_Takestep:	//	No color
			digitalWrite(led[BLUE], LOW);
			digitalWrite(led[RED], LOW);
			digitalWrite(led[GREEN], LOW);
			break;
		case Work_goCrane: 
		case Work_goBack:
		case Work_goTake: //	BLUE
			digitalWrite(led[BLUE], HIGH);
			digitalWrite(led[RED], LOW);
			digitalWrite(led[GREEN], LOW);
			break;
		case Pause:	//	RED twinkle
			if(twk == 0)
			{
				digitalWrite(led[BLUE], LOW);
				digitalWrite(led[RED], HIGH);
				digitalWrite(led[GREEN], LOW);
				twk = 1;
			}
			else
			{
				digitalWrite(led[BLUE], LOW);
				digitalWrite(led[RED], LOW);
				digitalWrite(led[GREEN], LOW);
				twk = 0;
			}
			break;
		case Acd:		//	RED 
			digitalWrite(led[BLUE], LOW);
			digitalWrite(led[RED], HIGH);
			digitalWrite(led[GREEN], LOW);
			break;
	}
}

void setup()
{
	// Initial set ESP32
	Initial_set();
	
	// Initial VC7300
	Initial_WI_SUN();
	
	// set timer 
	timer = timerBegin(0, 80, true);
	timerAttachInterrupt(timer, &onTimer, true);
	timerAlarmWrite(timer, 500, true);
	timerAlarmEnable(timer);
	
	//check Networking
	while(Cnn_flag == 0)
	{
		if(Serial2.available())
		{	
			Wi_SUN_RECEIVE();
		}
		if(millis() - cnn_timer > 10000)
		{
			Serial2.println(cn_send);
			cnn_timer = millis();
		}
		if(millis() - led_timer > 1000)
		{
			led_task(hold_registers[CarStatus_ADDR]);
			led_timer = millis();
		}
	}
	
	// Gain EEPROM
	gainEEPROM();
}

void loop()
{
	// 讀取 VC7300 輸入資料
	if(Serial2.available())
	{	
		Wi_SUN_RECEIVE();
	}
	
	// 超聲波測距作業
	if(millis() - ultrasound_timer > 300)
	{
		digitalWrite(echo_trig[trig], LOW);
		delayMicroseconds(2);
		digitalWrite(echo_trig[trig], HIGH);
		delayMicroseconds(10);
		digitalWrite(echo_trig[trig], LOW);
		duration = pulseIn(echo_trig[echo], HIGH);
		distance = duration * 0.034 / 2;
		if(hold_registers[MotorStatus_ADDR] == Motor_go)
		{
			if(distance < STOP_DISTANCE)	//注意tmp
			{
				hold_registerstmp = hold_registers[CarStatus_ADDR];
				hold_registers[CarStatus_ADDR] = Acd;
				Motor_Status(Motor_stop);
			}
		}
		else if(hold_registers[CarStatus_ADDR] == Acd)
		{
			if(distance > STOP_DISTANCE)
			{
				hold_registers[CarStatus_ADDR] = hold_registerstmp;
				hold_registerstmp = 0;
			}
		}
		ultrasound_timer = millis();
	}
	
	// 每 1 秒讀取AGV 設備狀態並變更 LED 燈狀態
	if(millis() - led_timer > 1000)
	{
		led_task(hold_registers[CarStatus_ADDR]);
		led_timer = millis();
	}
}