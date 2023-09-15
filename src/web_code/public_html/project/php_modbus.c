#include <sys/types.h>       /* basic system data types */
#include <sys/time.h>        /* timeval{} for select() */
#include <time.h>            /* timespec{} for pselect() */
#include <errno.h>
#include <fcntl.h>           /* for nonblocking */
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/wait.h>
#include <termios.h>

#define Serial_7O1	1
#define Serial_7E1	2
#define Serial_7N2	3
#define Serial_8N1	4
#define Serial_8O1	5
#define Serial_8E1	6
#define Serial_8N2	7

// Used Type Definitions
typedef unsigned char 	BYTE;
typedef unsigned short	WORD;

#define FALSE	0
#define TRUE	1
#define SIZE	200

#define MAXLINE		1000		

#ifndef HAVE_BZERO
#define bzero(ptr,n)        memset (ptr, 0, n)
#endif


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

void strtoupper(char *ptr)
{
	while (*ptr != '\0')
	{
		if ((*ptr >= 'a') && (*ptr <= 'z'))
			*ptr -= 32;
		
		ptr ++;
	}
}

int open_and_initialize_device(char *device, int baudrate, int encoding)
{
	struct termios	newtio;
	int	fd;			// file descriptor for serial port
	struct flock fl;		// definition for file lock
		
	// open the device to be non-blocking (read will return immediatly) and locked
	fl.l_type = F_WRLCK; // F_RDLCK, F_WRLCK, F_UNLCK 
	fl.l_whence = SEEK_SET; // SEEK_SET, SEEK_CUR, SEEK_END
	fl.l_start = 0; // Offset from l_whence 
	fl.l_len = 0; // length，0 = to EOF
	fl.l_pid = getpid(); // our PID
	
	fd = open(device, O_RDWR | O_NOCTTY | O_NONBLOCK);
	if (fd < 0)
		return fd;
	
	fcntl(fd, F_SETLKW, &fl);  // lock the device
		
	// set new port settings for canonical input processing
	bzero(&newtio, sizeof(newtio));

	// BAUDRATE: Set bps rate
	// CS8     : 8 data bits
	// CLOCAL  : local connection, no modem control
	// CREAD   : enable receiving characters
	switch(baudrate)
	{
		case 1200: newtio.c_cflag = B1200; break;
		case 2400: newtio.c_cflag = B2400; break;
		case 4800: newtio.c_cflag = B4800; break;
		case 9600: newtio.c_cflag = B9600; break;
		case 19200: newtio.c_cflag = B19200; break;
		case 38400: newtio.c_cflag = B38400; break;
		case 57600: newtio.c_cflag = B57600; break;
		case 115200: newtio.c_cflag = B115200; break;
		default: newtio.c_cflag = B38400; break;
	}
	
	switch(encoding)
	{
		case Serial_7O1: // 7O1
			newtio.c_cflag |= CS7 | CLOCAL | CREAD | PARENB | PARODD;
			newtio.c_cflag &= ~CSTOPB;
			break;
		case Serial_7E1: // 7E1
			newtio.c_cflag |= CS7 | CLOCAL | CREAD | PARENB;
			newtio.c_cflag &= ~CSTOPB;		// 1 stop bit
			newtio.c_cflag &= ~PARODD;
			break;
		case Serial_7N2: // 7N2
			newtio.c_cflag |= CS7 | CLOCAL | CREAD;
			newtio.c_cflag |= CSTOPB;		// 2 stop bits
			break;
		case Serial_8N1: // 8N1
			newtio.c_cflag |= CS8 | CLOCAL | CREAD;
			break;
		case Serial_8O1: // 8O1
			newtio.c_cflag |= CS8 | CLOCAL | CREAD | PARENB | PARODD;
			newtio.c_cflag &= ~CSTOPB;
			break;
		case Serial_8E1: // 8E1
			newtio.c_cflag |= CS8 | CLOCAL | CREAD | PARENB;
			newtio.c_cflag &= ~CSTOPB;		// 1 stop bit
			newtio.c_cflag &= ~PARODD;
			break;
		case Serial_8N2: // 8N2
			newtio.c_cflag |= CS8 | CLOCAL | CREAD;
			newtio.c_cflag |= CSTOPB;		// 2 stop bits
			break;
		default: // 8N1, Serial_8N1
			newtio.c_cflag |= CS8 | CLOCAL | CREAD | PARENB;
			newtio.c_cflag &= ~CSTOPB;		// 1 stop bit
			newtio.c_cflag &= ~PARODD;
			break;
	}

	newtio.c_iflag = IGNPAR;	// IGNPAR  : ignore bytes with parity errors
	newtio.c_oflag = 0;		// Raw output
	newtio.c_lflag = 0;		// set input mode (non-canonical, no echo,...)
	newtio.c_cflag &= ~CRTSCTS;	// disable hardware flow control

	// initialize all control characters 
	newtio.c_cc[VTIME]    = 0;   /* inter-character timer unused */
        newtio.c_cc[VMIN]     = 1;   /* blocking read until 1 chars received */

	// clean the serial port buffer and activate the settings for the port 
	tcflush(fd, TCIOFLUSH);
	tcsetattr(fd, TCSANOW, &newtio);

	return fd;
}

int my_serial_write(int fd, BYTE *vptr, int n)
{
        int nleft;
        int nwritten;
        BYTE *ptr;

        ptr = vptr;
        nleft = n;
        while (nleft > 0)
        {
                if ((nwritten = write(fd, ptr, nleft)) <= 0)
                {
                        if (nwritten < 0 && errno == EINTR)
                                nwritten = 0;   // and call write() again
                        else    return (-1);    // error
                }

                nleft -= nwritten;
                ptr += nwritten;
        }

        return (n);
}

static int read_cnt;
static BYTE *read_ptr;
static BYTE read_buf[MAXLINE];

ssize_t my_serial_read(int fd, BYTE *ptr)
{
	fd_set	readfs;			// file descriptor set
	int	res_select;		// for select()
	struct timeval timeout;		// for select() timeout
	
        if (read_cnt <= 0)
        {
		// set testing for source
		FD_ZERO(&readfs);
		FD_SET(fd, &readfs);
		timeout.tv_usec = (1000000) % 1000000ul;  /* useconds */
		timeout.tv_sec  = (1000000) / 1000000ul;  /* seconds */
		res_select = select(fd + 1, &readfs, NULL, NULL, &timeout);
		
		if ((res_select > 0) && (FD_ISSET(fd, &readfs)))
		{
			again:
			if ((read_cnt = read(fd, read_buf, sizeof(read_buf))) < 0)
			{
				if (errno == EINTR)
					goto again;

				return (-1);
			}
			else if (read_cnt == 0)
				return (0);

			read_ptr = read_buf;
		}
		else	// time out
		{
			return (-1);
		}
	}

        read_cnt--;
        *ptr = *read_ptr++;

        return (1);
}

// return buffer in Binary format
WORD serial_readline(int fd, BYTE *vptr, int maxlen)
{
        WORD	size, i, j;
	int	rc;
	BYTE    c, *ptr;
	BYTE	t1, t2;
	
	size = 0;
        ptr = vptr;
	
	while (my_serial_read(fd, &c) == 1)
	{
		size ++;
		*ptr = c;
		ptr ++;
		
		if (size >= maxlen)
			break;
	}
		
        return (size);
}

int send_to_device(int fd, BYTE *job, WORD length)
{
	WORD	i, j;
	BYTE	data[300];
	
	j = 0;
	for (i = 0; i < length; i ++)
	{
		data[j] = job[i];
		j ++;
	}
	
	// append 0X0D, 0X0A
	// data[j ++] = 0X0D;
	// data[j ++] = 0X0A;	
	data[j ++] = '\n';
		
	#ifdef DEBUG
	printf("send_frame_to_device_ASCII(): Frame= [");
	for (i = 0; i < j - 2; i ++)
		printf("%c", data[i]);
	printf("]\n");
	#endif

	i = my_serial_write(fd, data, j);
	
	if (j != i)
		return FALSE;
	return TRUE;
}

int main(int argc, char **argv) 
{
	BYTE	command[MAXLINE], request[MAXLINE], response[MAXLINE];
	int	serial_fd, res;
	BYTE	*p, len, i,j = 0,k = 0;
	char	Wi_SUN_TCP[MAXLINE], device[40] = {"/dev/ttyUSB0"}, Udp_tmp[MAXLINE];
	WORD	command_size, data_size, tcp_size, Transaction_id;

	if (argc < 3)
	{
		printf("argv[1]:USBPORT\n");
		printf("argv[2]:Modbus\n");
		exit(0);
	}
	
	//printf("Output : %s\n", device);
	// open device, 115200 bps, 8N1
	serial_fd = open_and_initialize_device(device, 115200, Serial_8N1);
	if (serial_fd < 0)
	{
			printf("Serial port open error: %s\n", device);
			exit(0);
	}
	
	bzero(response, MAXLINE);
	tcflush(serial_fd, TCIFLUSH); // clear input buffer
	
	// send to device
	//  php give ip argv[1]
	strcpy(command, "AT+USOCKSEND=0,5678,");
	strcat(command, "\"");
	strcat(command, argv[1]);
	strcat(command, "\",");
	//php write need fill 0
	data_size = strlen(argv[2]);
	if((data_size % 4) != 0)
	{
		printf("data Length error: %d\n", data_size);
		exit(0);
	}
	tcp_size = data_size + 12;
	sprintf(Udp_tmp, "%d", tcp_size);
	strcat(command, Udp_tmp);
	bzero(Udp_tmp, MAXLINE);
	strcat(command, ",\"");
	
	srand(time(0));
	Transaction_id = rand() % 65535;
	sprintf(Udp_tmp, "%04x", Transaction_id);
	strcat(command, Udp_tmp);
	bzero(Udp_tmp, MAXLINE);
	

	
	strcat(command, "0000");
	
	data_size /= 2; 
	sprintf(Udp_tmp, "%04x", data_size);
	strcat(command, Udp_tmp);
	bzero(Udp_tmp, MAXLINE);
	
	strcat(command, argv[2]);
	strcat(command, "\"");
	
	strtoupper(command);

	p = command;
	command_size = strlen(command);
	//printf("command: %s\n", command);
	res = send_to_device(serial_fd, command, command_size);
	
	if (res == FALSE)
	{
		printf("Serial port writing error: %s\n", device);
		exit(0);
	}
	
	// read device response Frame
	res = serial_readline(serial_fd, response, MAXLINE - 1);
	
	//printf("Response from slave device: [");
	for (i = 0; i < res; i ++)
	{
		if(response[i] == '+')
		{
			j++;
		}
		else if(j == 2)
		{
			
			if(response[i] == '\"')
			{
				k++;
				if(k == 4)
				{
					j++;
				}
				
			}
			else if(k == 3)
			{
				printf("%c", response[i]);
			}
		}
	}	//printf("]\n");
	close(serial_fd);			
}