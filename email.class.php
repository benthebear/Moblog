<?php

/**
 * A simple Class for handlings Emails
 * 
 * @author Benjamin Birkenhake <benjamin@birkenhake.org>
 *
 */
class email {
  public $server;
  public $port;
  public $user;
  public $password;
  public $directory;
  public $lastresult;
  public $lastresulttxt;
  public $connection;
  public $list;
  public $mails;
  
  function email($server, $port, $user, $password, $directory="files/"){
    $this->server = $server;
    $this->port = $port;
    $this->user = $user;
    $this->password = $password;
    $this->directory = $directory;
  }
  
  function email_open(){
    $this->connection = imap_open("{". $this->server.":".$this->port."/pop3}INBOX", $this->user, $this->password);
    if($this->connection){
    	//print "Connected";
    }else{
  		//sprint "Not connected";  	
    }    
  
  
  }
  
  function email_close(){
    imap_close($this->connection);
  }
  
  function get_all_mail(){
    $this->email_open();
    $number_of_messages = imap_num_msg($this->connection);
    //echo $number_of_messages;
    $counter = 1;
    while ($number_of_messages>=$counter){
      $this->get_one_mail($counter);
      $counter++;
    }
    $this->email_close();
  }
  
  function get_one_mail($number){
    $mail = imap_headerinfo($this->connection, $number);
    //echo "<hr/>";
    //echo "mail nummer: ".$number."<br/>";
    //echo "date: ".$mail->date."<br/>";
    //echo "subject: ".$mail->subject."<br/>";
    //echo "from-mailbox: ".$mail->from[0]->mailbox."<br/>";
    //echo "from-host: ".$mail->from[0]->host."<br/>";
    $this->mails[$number]["date"]["raw"] = $mail->date;
    $this->mails[$number]["date"]["timestamp"] = strtotime($mail->date);
   
    $last_char_1 = substr($mail->subject, -1, 1);
    $cleansubject = $mail->subject;
    $cleansubject = utf8_encode(imap_qprint($cleansubject));
    $cleansubject = ereg_replace("=\?ISO-8859-1\?Q\?", "", $cleansubject);
    $cleansubject = ereg_replace("\?=", "", $cleansubject);
    $cleansubject = ereg_replace("_", " ", $cleansubject);
    $last_char_2 = substr($cleansubject, -1, 1);
    if($last_char_1 != $last_char_2){
    	$cleansubject = substr($cleansubject, 0, strlen($cleansubject)-1);
    }
     
    //$cleansubject = ereg_replace("?", "", $cleansubject);
    
    $this->mails[$number]["subject"] = $cleansubject;
    $this->mails[$number]["mailbox"] = $mail->from[0]->mailbox;
    $this->mails[$number]["host"] = $mail->from[0]->host;
    $body = utf8_encode(imap_qprint(imap_fetchbody($this->connection, $number, "1")));
    if ($body!=""){
      $this->mails[$number]["text"] = $body;
    }
    $struct = imap_fetchstructure($this->connection, $number);
    //print_r($struct);
    $counter=2;
    while (imap_fetchbody($this->connection, $number, $counter)!=""){
      $image = imap_fetchbody($this->connection, $number, $counter);
      $this->mails[$number]["image"][$counter]["data"] = $image;
      $parts=$counter-1;
      $this->mails[$number]["image"][$counter]["name"] = $struct->parts[$parts]->dparameters[0]->value;
      $this->email_base64_to_file($number, $counter);
      $counter++;    
    }
  }
  
  function show_all_mail(){
    $this->get_all_mail();
    $counter =1;
    while ($this->mails[$counter]["date"]!=""){
      echo "<hr/>";
      echo "date: ".$this->mails[$counter]["date"]["raw"]."<br/>";
      echo "date: ".$this->mails[$counter]["date"]["timestamp"]." - ".date(" d. m. Y  H:i:s")."<br/>";    
      echo "subject: ".$this->mails[$counter]["subject"]."<br/>";
      echo "mailbox: ".$this->mails[$counter]["mailbox"]."<br/>";
      echo "host: ".$this->mails[$counter]["host"]."<br/>";
      echo "text: <br/>".nl2br($this->mails[$counter]["text"])."<br/>";
      $pic = 2;
      while ($this->mails[$counter]["image"][$pic]["data"]!=""){
        $pic--;
        echo "Bild Nr.".$pic."<br/>";
        $pic++;
        echo "Bild Name: <a href=\"/".$this->directory.$this->mails[$counter]["image"][$pic]["name"]."\">".$this->mails[$counter]["image"][$pic]["name"]."</a><br/>";
        //$this->email_base64_to_file($counter, $pic);
        //echo "image ".$pic.": ".$this->mails[$counter]["image"][$pic]["data"]."<br/>";
        $pic++;
      }      
      $counter++;    
    }
  }
  
  function email_base64_to_file($counter, $pic){
    $f_pic = fopen($this->directory.$this->mails[$counter]["image"][$pic]["name"], "w+");
    fwrite ($f_pic, base64_decode($this->mails[$counter]["image"][$pic]["data"]));
    fclose($f_pic);
  }
  
  function delete_all_mail(){
    $this->email_open();
    $counter=1;
    while ($this->mails[$counter]["date"]!=""){
      imap_delete($this->connection, $counter);
      $counter++;
    }
    imap_expunge($this->connection);
    $this->email_close();
  }
  
}


?>