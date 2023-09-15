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

int main(int argc, char **argv) 
{
	for(int x=3;x>0;x++)
		x--;
}