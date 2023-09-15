#include <esp_task_wdt.h>
#include <EEPROM.h>		

#define	SIZE		200
#define	LINELIMIT	300
#define PORTNO		5678	// local port no.
#define	TIMEOUT		2000
#define	TRUE		1
#define FALSE		0
#define SLAVE_ID	1
typedef word	WORD;
typedef	byte	BYTE;

//暫存器  
#define MAX_ADDR	 		20	// 最大暫存器數量
#define ADDR_CraneStatus 	1	// OHT 設備狀態
#define ADDR_EMTStatus 		2	// 電磁鐵狀態
#define ADDR_W_Cur	 		3	// W 軸位置
#define ADDR_X_Cur	 		4	// X 軸位置		
#define ADDR_Y_Cur	 		5	// Y 軸位置
#define ADDR_Z_Cur	 		6	// Z 軸位置					
#define ADDR_OP_CODE	 	7	// 運算碼
#define ADDR_OP_DATA	 	8	// 與運算碼一起寫入的資料	
	
#define ADDR_Car_axis		10	// 取貨四軸位置
#define ADDR_Warehouse_axis	14	// 置貨四軸位置
#define ADDR_z_initial_axis	18	// Z 軸初始位置


// OHT 設備狀態
#define	Idle				0	// 閒置
#define	Work				1	// 作業中
#define	Alarm				2	// 警告
#define Human_check			3	// 需人工檢查
#define	Wait_Connect			4	// 等待連線
#define	SUCCESSS_Work			5	// 完成作業

// 電磁鐵狀態
#define	EMT_IDLE			0	// 閒置
#define	EMT_Work			1	// 作業中

// 運算碼
#define	op_Nop				0 

// 自動操作
#define	op_Start			1 

// 手動操作
#define	op_EMT_Work			2	// 電磁鐵作業
#define	op_EMT_Release		3	// 電磁鐵不作業
#define	op_Gohome			4	// 四軸歸位
#define	op_W_Forward		5 	// W 軸向前
#define	op_W_BackWard		6	// W 軸向後		
#define	op_X_Forward		7	// X 軸向前		
#define	op_X_BackWard		8	// X 軸向後
#define	op_Y_Forward		9	// Y 軸向前	
#define	op_Y_BackWard		10	// Y 軸向後	
#define	op_Z_Forward		11	// Z 軸向前
#define	op_Z_BackWard		12	// Z 軸向後

#define	op_human_check		13	// 人工檢查完畢
#define	op_Reset			14	// 設備重置
#define	op_cnn				15	// 完成組網
#define	op_DONE				16	// 已完成作業

 /*
EEPROM 設定
EEPROM： 注意格式為 WORD, 一次使用兩格子(2 bytes), 因此需先宣告
EEPROM： 注意使用時須乘以 2 
*/
#define EEPROM_MAX				20		// 最大 EEPROM 數量
#define EEPROM_Car_axis			0		// 取貨四軸位置
#define EEPROM_Warehous_axis	8		// 置貨四軸位置
#define EEPROM_z_initial_axis	16		// Z 軸初始位置
#define EEPROM_EMT_Status		18		// 電磁鐵狀態

// 雙核心旗標與全域變數設定
TaskHandle_t 	Task1;
bool	work_flag = false;
bool	Reset_flag = false;
bool	Motor_Flag = false;
byte 	task_axis, task_dir;
int 	task_step;
bool	Cnn_flag = false;

// 電磁鐵設定
#define	emt_do	1
#define	emt_release	0
#define	emt_pin		25	

// 馬達設定
#define mm_step_w	39  			// 1 mm = 39 step
#define mm_step_x	100  			// 1 mm = 100 step
#define mm_step_y	100 			// 1 mm = 100 step
#define mm_step_z	100 			// 1 mm = 100 step			
#define dir_forward		1
#define dir_backward	0
#define	axis_w		0
#define	axis_x		1
#define	axis_y		2
#define	axis_z	 	3
#define W_LIMIT		100			// W 軸上限, 10 cm 100mm
#define X_LIMIT		180			// X 軸上限, 18 cm 180mm
#define Y_LIMIT		125			// Y 軸上限, 12.5 cm 125mm
#define Z_LIMIT		180			// Z 軸上限, 18 cm 180mm

//LEDs 燈設定
#define LED_PIN		4
byte twk = 0;

// 步進馬達驅動器接腳設定
BYTE		PUL[4]	= {15, 18, 14, 22};	// w,x,y,z
BYTE		DIR[4]	= {2, 19, 27, 23};	// 步進馬達 DIR 腳位，DO
BYTE		EN[4]	= {5, 21, 26, 13};
BYTE 		HOME[4] = {32, 33, 35, 34};

// TIMER 設定
unsigned long cnn_timer;
unsigned long led_timer;

// VC7300 訊息設定
char	cn_send[] = "AT+USOCKSEND=0,5678,\"fe80::fdff:ffff:f45a:e22\",4,\"CCCC\"";
int		udp_index;			// 執行 AT+USOCKREG 產生的 udp socket index
typedef struct
{
	WORD	fd_value;
	WORD	remote_port;
	char	remote_address[50];
	WORD	length;				// 必須是 4 的倍數
	char	data[LINELIMIT];
} UDP_PACKET;

//Modbus 設定
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
char		input_buf[LINELIMIT];
WORD		input_status, input_index;
unsigned long	input_timer;
BYTE		output_buf[LINELIMIT];
WORD		hold_registers[SIZE];

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
	
	return true;
}

// 初始化暫存器
void Initial_HR(void)								// Core 1
{
	for (int i = 0; i < MAX_ADDR; i ++)
		hold_registers[i] = 0;
}

// 重置設定
void Reset_ESP32(void)								// Core 1
{
	for (int i = 0; i < MAX_ADDR; i ++)
		hold_registers[i] = 0;
	
	for(int i = 0; i < EEPROM_MAX; i ++)
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
	for(i = 0; i < 4; i++)
	{
		pinMode(PUL[i], OUTPUT);
		pinMode(DIR[i], OUTPUT);
		pinMode(HOME[i], INPUT);
		pinMode(EN[i], OUTPUT);
	}
	pinMode(emt_pin, OUTPUT);
	pinMode(LED_PIN, OUTPUT);
	
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
	
	// 關閉四軸步進馬達電流
	digitalWrite(EN[axis_w], HIGH);
	digitalWrite(EN[axis_x], HIGH);
	digitalWrite(EN[axis_y], HIGH);
	digitalWrite(EN[axis_z], HIGH);
	
	EEPROM.begin(4096);	//設定 EEPROM 
	esp_task_wdt_init(10, false);	// 設定看門狗並關閉自動重新啟動

	// 開機等待 VC7300 組網並將電磁鐵設定為不作業
	hold_registers[ADDR_CraneStatus] = Wait_Connect;
	hold_registers[ADDR_EMTStatus] = EMT_IDLE;
	
	// 初始化全域變數
	input_status = 0;
	input_index = 0;
	input_timer = 0;
	
	// 記錄軟體時間
	led_timer = millis();
	cnn_timer = millis();
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
		if (parse_input(input_buf, &recv_udp, &recv_modbus) == true)
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

// 執行動作
void do_action()
{
	WORD	i, j, add = 0;	
	WORD EEPROM_GET;
	if(recv_modbus.function_code == 0X10)
	{	
		if(hold_registers[ADDR_CraneStatus] == Wait_Connect)	//等待組網
		{
			if((recv_modbus.starting_address == ADDR_OP_CODE) && recv_modbus.reg_values[0] == op_cnn)
			{
				digitalWrite(LED_PIN, HIGH);
				Cnn_flag = 1;
			}
		}
		if((recv_modbus.starting_address == ADDR_OP_CODE)) 
		{
			// 儲存運算碼一起寫入的資料
			for (i = 0; i < recv_modbus.no_of_points; i ++)
				hold_registers[recv_modbus.starting_address + i] = recv_modbus.reg_values[i];
			
			switch(hold_registers[ADDR_OP_CODE])
			{
				case op_Nop:	break;
				// 自動作業
				case op_Start:
					if(hold_registers[ADDR_CraneStatus] == Idle)
					{
						hold_registers[ADDR_CraneStatus] = Work;
						hold_registers[ADDR_EMTStatus] = EMT_Work;
						EEPROM.write(EEPROM_EMT_Status, hold_registers[ADDR_EMTStatus]);
						EEPROM.commit();
						work_flag = true;
					}
					break;
				// 手動作業
				case op_Gohome:
						Reset_flag = true;
					break;
				case op_EMT_Work:
						digitalWrite(emt_pin, emt_do);
						hold_registers[ADDR_EMTStatus] = EMT_Work;
						EEPROM.write(EEPROM_EMT_Status, hold_registers[ADDR_EMTStatus]);
						EEPROM.commit();
					break;
				case op_EMT_Release:
						digitalWrite(emt_pin, emt_release);
						hold_registers[ADDR_EMTStatus] = EMT_IDLE;
						EEPROM.write(EEPROM_EMT_Status, hold_registers[ADDR_EMTStatus]);
						EEPROM.commit();
					break;
				case op_W_Forward:	 W_move(hold_registers[ADDR_OP_DATA]);	break;
				case op_W_BackWard:	 W_move(- hold_registers[ADDR_OP_DATA]);	break;
				case op_X_Forward:	 X_move(hold_registers[ADDR_OP_DATA]);	break;
				case op_X_BackWard:  X_move(- hold_registers[ADDR_OP_DATA]);	break;
				case op_Y_Forward:	 Y_move(hold_registers[ADDR_OP_DATA]);	break;
				case op_Y_BackWard:	 Y_move(- hold_registers[ADDR_OP_DATA]);	break;
				case op_Z_Forward:	 Z_move(hold_registers[ADDR_OP_DATA]);	break;
				case op_Z_BackWard:	 Z_move(- hold_registers[ADDR_OP_DATA]);	break;
				
				// 人工檢查
				case op_human_check: 
					if(hold_registers[ADDR_CraneStatus] == Human_check)
						hold_registers[ADDR_CraneStatus] = Idle;
					break;
				// 重置
				case op_Reset:
					Reset_ESP32();
					break;
				// 作業完成
				case op_DONE:
					hold_registers[ADDR_CraneStatus] = Idle;
					break;
			}
		}
		else
		{
			for (i = 0; i < recv_modbus.no_of_points; i ++)
			{
				hold_registers[recv_modbus.starting_address + i] = recv_modbus.reg_values[i];
			}

			switch(recv_modbus.starting_address)
			{
				case ADDR_Car_axis:	// 取貨四軸位置
					for(j = 0; j < 4; j++)
					{
						EEPROM.write(EEPROM_Car_axis + add, hold_registers[ADDR_Car_axis + j] / 256);
						add++;
						EEPROM.write(EEPROM_Car_axis + add, hold_registers[ADDR_Car_axis + j] % 256);
						add++;
					}
					break;
				case ADDR_Warehouse_axis:	// 置貨四軸位置
					for(j = 0; j < 4; j++)
					{
						EEPROM.write(EEPROM_Warehous_axis + add, hold_registers[ADDR_Warehouse_axis + j] / 256);
						add++;
						EEPROM.write(EEPROM_Warehous_axis + add, hold_registers[ADDR_Warehouse_axis + j] % 256);
						add++;
					}
					break;
				case ADDR_z_initial_axis:	// Z 軸初始位置
					for(j = 0; j < 1; j++)
					{
						EEPROM.write(EEPROM_Warehous_axis + add, hold_registers[ADDR_Warehouse_axis + j] / 256);
						add++;
						EEPROM.write(EEPROM_Warehous_axis + add, hold_registers[ADDR_Warehouse_axis + j] % 256);
						add++;
					}
					break;
			}
			EEPROM.commit();
		}
	}
}
	
// 控制步進馬達
void Auto_Motor(BYTE c_axis, int mm_move, BYTE dir_move)			// Core 0
{
	int 	i, j;
	digitalWrite(EN[c_axis], LOW);
	switch (c_axis)
	{
		case axis_w: j = mm_move * mm_step_w; break;
		case axis_x: j = mm_move * mm_step_x; break;
		case axis_y: j = mm_move * mm_step_y; break;
		case axis_z: j = mm_move * mm_step_z; break;
	}
	
	digitalWrite(DIR[c_axis], dir_move);
	
	for (i = 0; i < j; i ++) //查看W軸是否需要分開
	{
		digitalWrite(PUL[c_axis], HIGH);               
		delayMicroseconds(150);
		digitalWrite(PUL[c_axis], LOW);
		delayMicroseconds(150);
	}
	digitalWrite(EN[c_axis], HIGH);    
}

// 步進馬達作業所需資料
void motor_move(BYTE c_axis, int mm_move, BYTE dir_move)			
{
	task_axis = c_axis;
	task_step = mm_move;
	task_dir = dir_move;
	Motor_Flag = true;
}

// W 軸移動距離
void W_move(int distance)							
{
	int	dd;
	
	dd = (int) hold_registers[ADDR_W_Cur] + distance;
	if (dd > W_LIMIT)
	{
		distance = W_LIMIT - hold_registers[ADDR_W_Cur];
		motor_move(axis_w, distance, dir_forward);
		dd = W_LIMIT;
	}
	else if (dd < 0)
	{	
		distance = (int) hold_registers[ADDR_W_Cur];
		motor_move(axis_w, distance, dir_backward);
		dd = 0;
	}
	else if ((int) hold_registers[ADDR_W_Cur] > dd)	
	{
		motor_move(axis_w, -distance, dir_backward);
	}
	else if ((int) hold_registers[ADDR_W_Cur] < dd)	
	{
		motor_move(axis_w, distance, dir_forward);
	}

	hold_registers[ADDR_W_Cur] = (WORD) dd;
	hold_registers[ADDR_CraneStatus] = Idle;
}

// X 軸移動距離
void X_move(int distance) 							
{
	int	dd;

	dd = (int) hold_registers[ADDR_X_Cur] + distance; 
	
	if (dd > X_LIMIT)
	{
		distance = X_LIMIT - hold_registers[ADDR_X_Cur];
		motor_move(axis_x, distance, dir_forward);
		dd = X_LIMIT;
	}
	else if (dd < 0)
	{	
		distance = (int) hold_registers[ADDR_X_Cur];
		motor_move(axis_x, distance, dir_backward);
		dd = 0;
	}
	else if ((int) hold_registers[ADDR_X_Cur] > dd)	
	{
		motor_move(axis_x, -distance, dir_backward);
	}
	else if ((int) hold_registers[ADDR_X_Cur] < dd)	
	{
		motor_move(axis_x, distance, dir_forward);
	}
	/*
	Serial.print("X_move : ");
	Serial.println(dd);
	Serial.print("mm");
	Serial.print("hold_registers[ADDR_X_Cur] : ");
	Serial.println(hold_registers[ADDR_X_Cur]);
	*/
	hold_registers[ADDR_X_Cur] = (WORD) dd;
	hold_registers[ADDR_CraneStatus] = Idle;
}

// Y 軸移動距離
void Y_move(int distance)						
{
	int	dd;
	
	dd = (int) hold_registers[ADDR_Y_Cur] + distance;
	
	if (dd > Y_LIMIT)
	{
		distance = Y_LIMIT - hold_registers[ADDR_Y_Cur];
		motor_move(axis_y, distance, dir_forward);
		dd = Y_LIMIT;
	}
	else if (dd < 0)
	{	
		distance = (int) hold_registers[ADDR_Y_Cur];
		motor_move(axis_y, distance, dir_backward);
		dd = 0;
	}
	else if ((int) hold_registers[ADDR_Y_Cur] > dd)	
	{
		motor_move(axis_y, -distance, dir_backward);
	}
	else if ((int) hold_registers[ADDR_Y_Cur] < dd)	
	{
		motor_move(axis_y, distance, dir_forward);
	}
	/*
	Serial.print("Y_move : ");
	Serial.println(dd);
	Serial.print("mm");
	Serial.print("hold_registers[ADDR_Y_Cur] : ");
	Serial.println(hold_registers[ADDR_Y_Cur]);
	*/
	hold_registers[ADDR_Y_Cur] = (WORD) dd;
	hold_registers[ADDR_CraneStatus] = Idle;
}

// Z 軸移動距離
void Z_move(int distance)							
{
	int	dd;
	
	dd = (int) hold_registers[ADDR_Z_Cur] + distance;
	
	if (dd > Z_LIMIT)
	{
		distance = Z_LIMIT - hold_registers[ADDR_Z_Cur];
		motor_move(axis_z, distance, dir_forward);
		dd = Z_LIMIT;
	}
	else if (dd < 0)
	{	
		distance = (int) hold_registers[ADDR_Z_Cur];
		motor_move(axis_z, distance, dir_backward);
		dd = 0;
	}
	else if ((int) hold_registers[ADDR_Z_Cur] > dd)	
	{
		motor_move(axis_z, -distance, dir_backward);
	}
	else if ((int) hold_registers[ADDR_Z_Cur] < dd)	
	{
		motor_move(axis_z, distance, dir_forward);
	}
	/*
	Serial.print("Z_move : ");
	Serial.println(dd);
	Serial.print("mm");
	Serial.print("hold_registers[ADDR_Z_Cur] : ");
	Serial.println(hold_registers[ADDR_Z_Cur]);
	*/
	hold_registers[ADDR_Z_Cur] = (WORD) dd;
	hold_registers[ADDR_CraneStatus] = Idle;
}

// 取得 ESP32 FLASH 資料
void	gainEEPROM()
{
	byte  EEPROM_GETData;
	BYTE  i,add = 0;
	
	// 取得取貨四軸位置
	for(i = 0; i < 8;i++)
	{
		EEPROM_GETData = 0; 
		EEPROM_GETData = EEPROM.read(EEPROM_Car_axis + add);
		hold_registers[ADDR_Car_axis + i] = hold_registers[ADDR_Car_axis + i] + EEPROM_GETData << 8;
		add++;
		EEPROM_GETData = 0; 
		EEPROM_GETData = EEPROM.read(EEPROM_Car_axis + add);
		hold_registers[ADDR_Car_axis + i] = hold_registers[ADDR_Car_axis + i] + EEPROM_GETData;
		add++;
	}
	add = 0;
	
	// 取得置貨四軸位置
	for(i = 0; i < 8;i++)
	{
		EEPROM_GETData = 0; 
		EEPROM_GETData = EEPROM.read(EEPROM_Warehous_axis + add);
		hold_registers[ADDR_Warehouse_axis + i] = hold_registers[ADDR_Warehouse_axis + i] + EEPROM_GETData << 8;
		add++;
		EEPROM_GETData = 0; 
		EEPROM_GETData = EEPROM.read(EEPROM_Warehous_axis + add);
		hold_registers[ADDR_Warehouse_axis + i] = hold_registers[ADDR_Warehouse_axis + i] + EEPROM_GETData;
		add++;
	}

	// 取得 Z 軸初始位置
	for(i = 0; i < 2;i++)
	{
		EEPROM_GETData = 0; 
		EEPROM_GETData = EEPROM.read(EEPROM_z_initial_axis + add);
		hold_registers[ADDR_z_initial_axis + i] = hold_registers[ADDR_z_initial_axis + i] + EEPROM_GETData << 8;
	}
	
	// 取得電磁鐵狀態
	if(EEPROM.read(EEPROM_EMT_Status) == EMT_Work)
		hold_registers[ADDR_CraneStatus] = Human_check;
}

// 核心 0 作業
void Task1_senddata(void * pvParameters)					
{
	for (;;) 
	{
		byte	i,j;
		if(work_flag)	// 自動作業
		{
			WORD	w, x, y, z;
			
			Motor_Home(); // 歸位作業
			
			// 取貨四軸作業
			w = hold_registers[ADDR_Car_axis + axis_w];
			x = hold_registers[ADDR_Car_axis + axis_x];
			y = hold_registers[ADDR_Car_axis + axis_y];
			z = hold_registers[ADDR_Car_axis + axis_z];
			Auto_Coordinate_set_wxyz(w, x, y, z);
			digitalWrite(emt_pin, emt_do);
			
			// 電磁鐵作業
			hold_registers[ADDR_EMTStatus] = EMT_Work;
			Auto_Coordinate_set_z(hold_registers[ADDR_z_initial_axis]);		
			EEPROM.write(EEPROM_EMT_Status, hold_registers[ADDR_EMTStatus]);
			EEPROM.commit();

			// 置貨四軸作業
			w = hold_registers[ADDR_Warehouse_axis + axis_w];
			x = hold_registers[ADDR_Warehouse_axis + axis_x];
			y = hold_registers[ADDR_Warehouse_axis + axis_y];
			z = hold_registers[ADDR_Warehouse_axis + axis_z];
			Auto_Coordinate_set_wxyz(w, x, y, z);
			
			// 電磁鐵不作業
			digitalWrite(emt_pin, emt_release);
			hold_registers[ADDR_EMTStatus] = EMT_IDLE;
			EEPROM.write(EEPROM_EMT_Status, hold_registers[ADDR_EMTStatus]);
			EEPROM.commit();
			
			Motor_Home();	// 歸位作業
			
			// 完成作業
			hold_registers[ADDR_CraneStatus] = SUCCESSS_Work;
			work_flag = false;
		}
		else if(Reset_flag)	//歸位作業
		{
			Motor_Home();
			
			Reset_flag = false;
		}
		else if(Motor_Flag)	//馬達移動作業
		{
			Auto_Motor(task_axis, task_step, task_dir);
			Motor_Flag = false;
		}
		else
		{
			delay(1);	//Task1休息，delay(1)不可省略
		}
	}
}

// 自動作業初始 Z 軸距離
void Auto_Coordinate_set_z(WORD z_target)				
{
	int	dd, target;
	
	target = (int) z_target;
	if (hold_registers[ADDR_Z_Cur] != target)
	{
		if (target > Z_LIMIT)
		{
			dd = Z_LIMIT - hold_registers[ADDR_Z_Cur];
			Auto_Motor(axis_z, dd, dir_forward);
			target = Z_LIMIT;
		}
		else if (target < 0)
		{	
			dd = (int) hold_registers[ADDR_Z_Cur];
			Auto_Motor(axis_z, dd, dir_backward);
			target = 0;
		}
		else if (hold_registers[ADDR_Z_Cur] > target)
		{
			dd = (int) hold_registers[ADDR_Z_Cur] - target;
			Auto_Motor(axis_z, dd, dir_backward);
		}
		else if (hold_registers[ADDR_Z_Cur] < target)
		{
			dd = target - (int) hold_registers[ADDR_Z_Cur];
			Auto_Motor(axis_z, dd, dir_forward);
		}
		
		hold_registers[ADDR_Z_Cur] = (WORD) target;
	}
	
}

// 自動作業四軸距離
void Auto_Coordinate_set_wxyz(WORD w_target, WORD x_target, WORD y_target, WORD z_target)			// Core 0
{
	int	dd, target;
	
	target = (int) w_target;
	
	if (hold_registers[ADDR_W_Cur] != target)
	{
		if (target > W_LIMIT)
		{
			dd = X_LIMIT - hold_registers[ADDR_W_Cur];
			Auto_Motor(axis_w, dd, dir_forward);
			target = X_LIMIT;
		}
		else if (target < 0)
		{	
			dd = (int) hold_registers[ADDR_W_Cur];
			Auto_Motor(axis_w, dd, dir_backward);
			target = 0;
		}
		else if (hold_registers[ADDR_W_Cur] > target)
		{
			dd = (int) hold_registers[ADDR_W_Cur] - target;
			Auto_Motor(axis_w, dd, dir_backward);
		}
		else if (hold_registers[ADDR_W_Cur] < target)
		{
			dd = target - (int) hold_registers[ADDR_W_Cur];
			Auto_Motor(axis_w, dd, dir_forward);
		}
		
		hold_registers[ADDR_W_Cur] = (WORD) target;
	}
	
	target = (int) x_target;
	
	if (hold_registers[ADDR_X_Cur] != target)
	{
		if (target > X_LIMIT)
		{
			dd = X_LIMIT - hold_registers[ADDR_X_Cur];
			Auto_Motor(axis_x, dd, dir_forward);
			target = X_LIMIT;
		}
		else if (target < 0)
		{	
			dd = (int) hold_registers[ADDR_X_Cur];
			Auto_Motor(axis_x, dd, dir_backward);
			target = 0;
		}
		else if (hold_registers[ADDR_X_Cur] > target)
		{
			dd = (int) hold_registers[ADDR_X_Cur] - target;
			Auto_Motor(axis_x, dd, dir_backward);
		}
		else if (hold_registers[ADDR_X_Cur] < target)
		{
			dd = target - (int) hold_registers[ADDR_X_Cur];
			Auto_Motor(axis_x, dd, dir_forward);
		}
		
		hold_registers[ADDR_X_Cur] = (WORD) target;
	}
	
	target = (int) y_target;
	
	if (hold_registers[ADDR_Y_Cur] != target)
	{
		if (target > Y_LIMIT)
		{
			dd = Y_LIMIT - hold_registers[ADDR_Y_Cur];
			Auto_Motor(axis_y, dd, dir_forward);
			target = Y_LIMIT;
		}
		else if (target < 0)
		{	
			dd = (int) hold_registers[ADDR_Y_Cur];
			Auto_Motor(axis_y, dd, dir_backward);
			target = 0;
		}
		else if (hold_registers[ADDR_Y_Cur] > target)
		{
			dd = (int) hold_registers[ADDR_Y_Cur] - target;
			Auto_Motor(axis_y, dd, dir_backward);
			
		}
		else if (hold_registers[ADDR_Y_Cur] < target)
		{
			dd = target - (int) hold_registers[ADDR_Y_Cur];
			Auto_Motor(axis_y, dd, dir_forward);
		}
		
		hold_registers[ADDR_Y_Cur] = (WORD) target;
	}
	
	target = (int) z_target;
	
	if (hold_registers[ADDR_Z_Cur] != target)
	{
		if (target > Z_LIMIT)
		{
			dd = Z_LIMIT - hold_registers[ADDR_Z_Cur];
			Auto_Motor(axis_z, dd, dir_forward);
			target = Z_LIMIT;
		}
		else if (target < 0)
		{	
			dd = (int) hold_registers[ADDR_Z_Cur];
			Auto_Motor(axis_z, dd, dir_backward);
			target = 0;
		}
		else if (hold_registers[ADDR_Z_Cur] > target)
		{
			dd = (int) hold_registers[ADDR_Z_Cur] - target;
			Auto_Motor(axis_z, dd, dir_backward);
		}
		else if (hold_registers[ADDR_Z_Cur] < target)
		{
			dd = target - (int) hold_registers[ADDR_Z_Cur];
			Auto_Motor(axis_z, dd, dir_forward);
		}
		
		hold_registers[ADDR_Z_Cur] = (WORD) target;
	}
}

// 自動歸位
void Motor_Home(void)
{
	WORD	i;
	
	// 電磁鐵不作業
	digitalWrite(emt_pin, emt_release);
	hold_registers[ADDR_EMTStatus] = Idle;
	EEPROM.write(EEPROM_EMT_Status, hold_registers[ADDR_EMTStatus]);
	EEPROM.commit();
	
	// 四軸步進馬達通電
	digitalWrite(EN[axis_w], LOW);
	digitalWrite(EN[axis_x], LOW);
	digitalWrite(EN[axis_y], LOW);
	digitalWrite(EN[axis_z], LOW);
	
	// 四軸步進馬達向後
	digitalWrite(DIR[axis_w], LOW);
	digitalWrite(DIR[axis_x], LOW);
	digitalWrite(DIR[axis_y], LOW);
	digitalWrite(DIR[axis_z], LOW);

	// Z 軸歸位
	for (i = 0; i < Z_LIMIT * mm_step_z; i ++)
	{
		if (digitalRead(HOME[axis_z]) == 1)
			break;
		
		digitalWrite(PUL[axis_z], HIGH);               
		delayMicroseconds(150);
		digitalWrite(PUL[axis_z], LOW);
		delayMicroseconds(150);
	}
	
	// Y 軸歸位
	for (i = 0; i < Y_LIMIT * mm_step_y; i ++)
	{
		if (digitalRead(HOME[axis_y]) == 1)
			break;
		
		digitalWrite(PUL[axis_y], HIGH);               
		delayMicroseconds(150);
		digitalWrite(PUL[axis_y], LOW);
		delayMicroseconds(150);
	}
	
	// X 軸歸位
	for (i = 0; i < X_LIMIT * mm_step_x; i ++)
	{
		if (digitalRead(HOME[axis_x]) == 1)
			break;

		digitalWrite(PUL[axis_x], HIGH);               
		delayMicroseconds(150);
		digitalWrite(PUL[axis_x], LOW);
		delayMicroseconds(150);
	}
	
	// W 軸歸位 
	for (i = 0; i < W_LIMIT * mm_step_w; i ++)
	{
		if (digitalRead(HOME[axis_w]) == 1)
			break;

		digitalWrite(PUL[axis_w], HIGH);               
		delayMicroseconds(150);
		digitalWrite(PUL[axis_w], LOW);
		delayMicroseconds(150);
	}
	
	// 四軸目前位置為 0
	hold_registers[ADDR_X_Cur] = 0;
	hold_registers[ADDR_Y_Cur] = 0;
	hold_registers[ADDR_Z_Cur] = 0;
	hold_registers[ADDR_W_Cur] = 0;
	
	// 四軸步進馬達不通電
	digitalWrite(EN[axis_w], HIGH);
	digitalWrite(EN[axis_x], HIGH);
	digitalWrite(EN[axis_y], HIGH);
	digitalWrite(EN[axis_z], HIGH);

	if((hold_registers[ADDR_CraneStatus] != Work) || (hold_registers[ADDR_CraneStatus] != Human_check))
		hold_registers[ADDR_CraneStatus] = Idle;
}

// LEDs 顯示作業
void led_task()
{
	if(hold_registers[ADDR_CraneStatus] == Wait_Connect)	// GREEN twinkle
	{
		if(twk == 0)
		{
			digitalWrite(LED_PIN, HIGH);
			twk = 1;
		}
		else
		{
			digitalWrite(LED_PIN, LOW);
			twk = 0;
		}
	}
	else
	{
		digitalWrite(LED_PIN, HIGH);	// GREEN 
	}
	
}

void setup()
{
	// Initial set ESP32
	Initial_set();
	
	// Initial VC7300
	Initial_WI_SUN();
	
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
			led_task();
			led_timer = millis();
		}		
	}
	
	// Gain EEPROM
	gainEEPROM();
	
	//歸位作業
	Reset_flag = true;
}

void loop()
{
	// 讀取 VC7300 輸入資料
	if(Serial2.available())
	{	
		Wi_SUN_RECEIVE();
	}
}