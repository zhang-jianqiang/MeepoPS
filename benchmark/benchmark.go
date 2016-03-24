package main

import (
	"fmt"
	"net"
)

func main(){
	client, err := net.Dial("tcp","127.0.0.1:19910");
	if err!=nil {
		fmt.Println("服务端连接失败");
		return;
	}
	defer client.Close();
	buf := make([]byte,1024);
	strlen, err := client.Read(buf);
	if err!=nil || strlen <= 0{
		fmt.Println(err.Error());
		return;
	}
	fmt.Println("收到响应:" + string(buf));

	client.Write([]byte("你好,服务端!"));
	buf = make([]byte,1024);
	strlen, err = client.Read(buf);
	if err!=nil {
		fmt.Println(err.Error());
		return;
	}
	if strlen <= 0 {
		fmt.Println("没有收到数据");
		return;
	}
	fmt.Println("收到响应:" + string(buf));
}