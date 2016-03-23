package main
import(
	."fmt"
	"net"
	"os"
//	"io/ioutil"
	"bufio"
)
func checkOk(ok error){
	if ok != nil {
		Println("Error", ok)
		os.Exit(1)
	}
}
func main(){
	ipString := "127.0.0.1:19910"
	data := "go - test"
	tcpAddr, ok := net.ResolveTCPAddr("tcp", ipString)
	checkOk(ok)
	conn, ok := net.DialTCP("tcp", nil, tcpAddr)
	checkOk(ok)
	defer conn.Close()
	Println("123")
	conn.Write([]byte(data))
	Println("456")
	res, ok := bufio.NewReader(conn).ReadString('\n')
//	res, ok := ioutil.ReadAll(conn)
	checkOk(ok)
	Println(res)
//	os.Exit(0)
}