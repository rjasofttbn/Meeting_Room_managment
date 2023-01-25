<?php
/**
 * MailSend.php
 *
 * @category   Email Transport
 * @package    MailSend
 * @author     Andy Prevost <andy@codeworxtech.com>
 * @copyright  2004-2022 (C) Andy Prevost - All Rights Reserved
 * @version    0.9.2b
 * @license    MIT - Distributed under the MIT License, available at:
 *             http://www.opensource.org/licenses/mit-license.html
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
**/
/* Last updated on: 2022-05-01 18:48:02 (EST) */

namespace codeworxtech;

if (version_compare(PHP_VERSION, '7.2.0', '<=') ) { exit("Sorry, this version of SendMail will only run on PHP version 7.2 or greater!\n"); }

class MailSend {

  /* CONSTANTS */
  const VERSION   = '0.9.2b';
  const EOL       = "\r\n";
  const MAILSEP   = ", ";

  /* SMTP CONSTANTS */
  const TIMEVAL   = 30; // seconds
  const PASSMK    = '&#10003; '; //checkmark
  const FAILMK    = '&#10007; '; //x

  /* PROPERTIES, PRIVATE & PROTECTED */
  private $addparams      = '';
  private $attachments    = [];
  private $bcc            = '';
  private $boundary       = [];
  private $charset        = 'utf-8';
  private $cc             = '';
  private $confirm_read   = ''; // https://www.rfc-editor.org/rfc/rfc3798
  private $custom_hdr     = [];
  private $encode_hdr     = 'base64';
  private $encode_msg     = '8bit';
  public  $MessageICal    = '';
  public  $MessageHTML    = '';
  public  $MessageText    = '';
  private $MessageType    = '';
  private $num_inline     = 0;
  public  $Priority       = 0;
  private $recipients     = '';
  private $recipients_rt  = '';
  private $replyTo        = '';
  private $sender         = '';
  private $SendmailPath   = '';
  public  $Subject        = '';
  private $tot_attach     = [];
  private $wraplen        = 70; // from PHP manual
  public  $useSMTP        = true;

  /* SMTP PROPERTIES, PUBLIC PRIVATE & PROTECTED */
  public  $SMTP_Account   = [];
  public  $SMTP_Domain    = '';
  private $SMTP_fdbk      = [];
  public  $SMTP_From      = '';
  public  $SMTP_Host      = '';
  public  $SMTP_KeepAlive = false;
  public  $SMTP_Options   = []; // ['ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ] ];
  public  $SMTP_Password  = '';
  public  $SMTP_Port      = '';
  private $SMTP_Stream    = 0;
  public  $SMTP_Username  = '';
  public  $SMTP_Useverp   = false;
  public  $SMTP_Debug     = 0;

  /* METHODS ************/

  /**
   * Class Constructor
   */
  function __construct() {
    // Generate boundaries for mail content areas
    $this->boundary['wrap'] = md5(uniqid(time()+1) . uniqid()) . '_w1';
    $this->boundary['body'] = md5(uniqid(time()+2) . uniqid()) . '_b1';
    // Get 'sendmail' path
    $this->SendmailPath = strtok(ini_get('sendmail_path'),' ') . ' -t -oi';
    // Get mail domain name for this server (mx record)
    $this->SMTP_Domain = self::GetMailServer();
  }

  /**
   * Class Destructor
   */
  function __destruct() {
    self::Clear();
    if (self::SMTP_IsStreamConnected()) {
      self::SMTP_Quit();
    }
    if ($this->SMTP_Debug > 0 && count($this->SMTP_fdbk) > 0) {
      foreach ($this->SMTP_fdbk as $msg) {
        echo $msg;
      }
    }
  }

  /**
   * Adds an attachment from a path on the filesystem.
   * Returns false if the file could not be found
   * or accessed.
   * @param string $path Path to the attachment.
   * @param string $name Overrides the attachment name.
   * @param string $encoding File encoding (see $Encoding).
   * @param string $type File extension (MIME) type.
   * @return bool
   */
  public function AddAttachment($path, $name='', $encoding='base64', $type='') {
    static::IsExploitPath($path);
    if ($type == '' && function_exists('mime_content_type')) {
      $mimeType = mime_content_type($path);
    } else {
      $mimeType = 'application/octet-stream';
    }
    $filename = basename($path);
    if ($name == '') { $name = $filename; }
    $this->attachments[] = [ 0=>$path,1=>$filename,2=>$name,3=>$encoding,4=>$mimeType,5=>false,6=>'attachment',7=>0 ];
    return true;
  }

  /**
   * Add a BCC
   * @param string $bcc
   */
  public function AddBCC($param) {
    if (is_string($param)) {
      $param = [$param=>''];
    } elseif (is_array($param)) {
      foreach ($param as $key => $val) {
        $arr   = [$key=>$val];
        $data  = self::ReorderArray($arr);
        $name  = reset($data);
        $email = key($data);
        $sep   = (trim($this->bcc) != '') ? self::MAILSEP : '';
        $this->bcc .= $sep . self::AddrFormatRFC2822($arr);
        $sep   = (trim($this->recipients_rt) != '') ? self::MAILSEP : '';
        $this->recipients_rt .= $sep . $email;
      }
    }
  }

  /**
   * Add a CC
   * @param string $cc
   */
  public function AddCC($param) {
    if (is_string($param)) {
      $param = [$param=>''];
    } elseif (is_array($param)) {
      foreach ($param as $key => $val) {
        $arr   = [$key=>$val];
        $data  = self::ReorderArray($arr);
        $name  = reset($data);
        $email = key($data);
        $sep   = (trim($this->cc) != '') ? self::MAILSEP : '';
        $this->cc .= $sep . self::AddrFormatRFC2822($arr);
        $sep   = (trim($this->recipients_rt) != '') ? self::MAILSEP : '';
        $this->recipients_rt .= $sep . $email;
      }
    }
  }

  /**
   * Adds a custom header
   */
  public function AddCustomHeader($custom_header) {
    $this->custom_hdr[] = explode(':', $custom_header, 2);
  }

  /**
   * Adds an embedded attachment. This can include images (backgrounds,etc).
   * @param string $path Path (location) of attachment.
   * @param string $cid Content ID of the attachment. Use to identify
   *        the Id for accessing the image in an HTML doc.
   * @param string $name Overrides the attachment name.
   * @param string $encoding Mime encoding.
   * @param string $type File extension.
   * @return bool
   */
  public function AddEmbeddedImage($path, $cid, $name='', $encoding='base64', $type='') {
    static::IsExploitPath($path);
    if ( !@is_file($path) ) { return false; }
    if ($type == '') {
      $type = (function_exists('mime_content_type')) ? mime_content_type($path) : 'application/octet-stream';
    }
    $filename = basename($path);
    if ($name == '') { $name = $filename; }
    // Append to $attachment array (5 is String Attachment)
    $this->attachments[] = [ 0 => $path, 1 => $filename, 2 => $name, 3 => $encoding, 4 => $type, 5 => false, 6 => 'inline', 7 => $cid ];
    $this->num_inline++;
    return true;
  }

  /**
   * Add a recipient
   * @param string $email
   */
  public function AddRecipient($param) {
    if (is_string($param)) {
      $param = [$param=>''];
    } elseif (is_array($param)) {
      foreach ($param as $key => $val) {
        $arr   = [$key=>$val];
        $data  = self::ReorderArray($arr);
        $name  = reset($data);
        $email = key($data);
        $sep   = (trim($this->recipients) != '') ? self::MAILSEP : '';
        $this->recipients .= $sep . self::AddrFormatRFC2822($arr);
        $sep   = (trim($this->recipients_rt) != '') ? self::MAILSEP : '';
        $this->recipients_rt .= $sep . $email;
      }
    }
  }

  /**
   * Structures email address/name as defined in RFC 2822
   * https://www.rfc-editor.org/rfc/rfc2822
   * @param array (or string - detect)
   * @return string
   */
  private function AddrFormatRFC2822($param,$raw=false) {
    $param = self::ObjectToArray($param);
    $addr_str = '';
    foreach ($param as $var1 => $var2) {
      $data  = self::ReorderArray( [ $var1 => $var2 ] );
      $name  = reset($data);
      $email = key($data);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit('Bad Email: "' . $email . '"');
      }
      if (trim($name) != '' && $raw === false) {
        $addr_str .= '"' . self::MS_Mb_Encode($name) . '" <' . $email . '>, ';
      } elseif (trim($name) != '' && $raw !== false) {
        $addr_str .= '"' . $name . '" <' . $email . '>, ';
      } else {
        $addr_str .= '<'.$email.'>, ';
      }
    }
    return rtrim($addr_str, ', ');
  }

  /**
   * Build attachment
   * @param string $attachment
   * @return string
   */
  private function BuildAttachment($attachment='',$bkey='wrap') {
    $mime = $cidUniq = $incl = [];
    // add parameter passed in function
    if ($attachment != '' && is_string($attachment)) {
      if (self::IsPathSafe($attachment) !== true) { return false; }
      $mimeType = (function_exists('mime_content_type')) ? mime_content_type($attachment) : 'application/octet-stream';
      $fileContent = file_get_contents($attachment);
      $fileContent = chunk_split(base64_encode($fileContent));
      $data  = 'Content-Type: ' . $mimeType . '; name=' . basename($attachment) . self::EOL;
      $data .= 'Content-Transfer-Encoding: ' . $this->encode_hdr . self::EOL;
      $data .= 'Content-ID: <' . basename($attachment) . '>' . self::EOL;
      $data .= self::EOL . $fileContent . self::EOL . self::EOL;
      $data  = self::GetBoundary($bkey) . $data;
      $this->tot_attach[] = $attachment;
      return $data . self::EOL . self::EOL;
    }

    // Add all other attachments and check for string attachment
    $bString = $attachment[5];
    if ($bString) {
      $string = $attachment[0];
    } else {
      $path = $attachment[0];
      if (self::IsPathSafe($path) !== true) { return false; }
    }

    if (in_array($path, $this->tot_attach)) { return; }
    if (in_array($path, $incl)) { return; }

    $filename    = $attachment[1];
    $name        = $attachment[2];
    $encoding    = $attachment[3];
    $type        = $attachment[4];
    $disposition = $attachment[6];
    $cid         = $attachment[7];
    $incl[]      = $attachment[0];

    $this->tot_attach[] = $path;

    if ( $disposition == 'inline' && isset($cidUniq[$cid]) ) { return; }
    $cidUniq[$cid] = true;

    $mime[] = 'Content-Type: ' . $type . '; name="' . $name . '"' . self::EOL;
    $mime[] = 'Content-Transfer-Encoding: ' . $encoding . self::EOL;

    if($disposition == 'inline') {
      $mime[] = 'Content-ID: <'.$cid.'>' . self::EOL;
    }
    $mime[] = 'Content-Disposition: ' . $disposition . '; filename="' . $name . '"' . self::EOL . self::EOL;

    // Encode as string attachment
    if($bString) {
      $mime[] = chunk_split(base64_encode($string), $this->wraplen, self::EOL);
      $mime[] = self::EOL . self::EOL;
    } else {
      $mime[] = chunk_split(base64_encode( file_get_contents($path) ), $this->wraplen, self::EOL);
      $mime[] = self::EOL . self::EOL;
    }
    $data = implode('', $mime);
    $data  = self::GetBoundary($bkey) . $data;
    return $data . self::EOL . self::EOL;;
  }

  /**
   * Builds the message body
   * @return string All parts of the body (text, HTML, attachments)
   */
  private function BuildBody() {
    static::IsExploitPath($this->MessageHTML);
    static::IsExploitPath($this->MessageICal);
    if (is_file($this->MessageHTML)) {
      $thisdir = (dirname($this->MessageHTML) != '') ? rtrim(dirname($this->MessageHTML),'/') . '/' : '';
      self::Data2HTML(file_get_contents($this->MessageHTML),$thisdir);
      self::getMsgType();
    }
    if (is_file($this->MessageICal)) {
      $this->MessageICal = file_get_contents($this->MessageICal);
    }

    $gBEnd = '';
    $body  = self::EOL;
    $body .= 'This is a multipart message in MIME format.' . self::EOL;
    $body .= self::EOL;
    // wrapper
    if ($this->MessageType != 'message' && $this->MessageType != 'ics') {
      $gBEnd = self::GetBoundary('wrap','--');
      $body .= self::GetBoundary('wrap');
      if ($this->MessageType == 'attachment_inline_message') {
        $body .= self::GetContentTypeHdr('multipart/related','body','hdr') . self::EOL;
      } else {
        $body .= self::GetContentTypeHdr('multipart/alternative','body','hdr') . self::EOL;
      }
      $body .= self::EOL;
    }
    // inline only
    if ($this->MessageType == 'inline') {
      $body .= self::GetBoundary('body');
      $body .= self::GetContentTypeBody('text/plain','charset="us-ascii"','7bit') . self::EOL;
      $body .= self::EOL;
      $body .= self::GetBoundary('body');
      $body .= self::EOL;
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'inline') {
          $body .= self::GetBoundary('wrap');
          $body .= self::GetContentTypeBody($attachment[4],'name="'.$attachment[1].'"','base64',$attachment[7]) . self::EOL;
        }
      }
      $body .= self::GetBoundary('wrap');
    }
    // attachment only
    elseif ($this->MessageType == 'attachment') {
      $body .= self::GetBoundary('body');
      $body .= self::GetContentTypeBody('text/plain','charset="us-ascii"','7bit') . self::EOL;
      $body .= self::EOL;
      $body .= self::GetBoundary('body','--');
      $body .= self::EOL;
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'attachment') {
          $body .= self::BuildAttachment($attachment,'wrap');
        }
      }
    }
    // message only
    elseif ($this->MessageType == 'message') {
      $body .= self::GetMsgPart('wrap');
      if (!empty(trim($this->MessageICal))) { $body .= self::EOL; $body .= self::GetIcsPart('wrap'); }
      $body .= self::EOL;
      $body .= self::GetBoundary('wrap','--');
    }
    // ics only
    elseif ($this->MessageType == 'ics') {
      if(!empty(trim($this->MessageICal))) {
        $body .= self::GetIcsPart('none');
      }
    }
    // message with inline (iCalendar option)
    elseif ($this->MessageType == 'inline_message' || $this->MessageType == 'ics_inline_message') {
      $body .= self::GetMsgPart('body');
      if (!empty(trim($this->MessageICal))) { $body .= self::EOL; $body .= self::GetIcsPart('body'); }
      $body .= self::EOL;
      $body .= self::GetBoundary('body','--');
      $body .= self::EOL;
      // inline
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'inline') {
          $body .= self::BuildAttachment($attachment,'wrap');
        }
      }
    }
    // message with attachment (iCalendar option)
    elseif ($this->MessageType == 'attachment_message' || $this->MessageType == 'attachment_ics_message') {
      $body .= self::GetMsgPart('body');
      if (!empty(trim($this->MessageICal))) { $body .= self::EOL; $body .= self::GetIcsPart('body'); }
      $body .= self::EOL;
      $body .= self::GetBoundary('body','--');
      $body .= self::EOL;
      // attachment
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'attachment') {
          $body .= self::BuildAttachment($attachment,'wrap');
        }
      }
    }
    // message with attachment
    elseif ($this->MessageType == 'attachment_inline_message') {
      $this->boundary['spec'] = md5(uniqid(time()+3) . uniqid()) . '_b2';
      $body .= self::GetBoundary('body');
      $body .= self::GetContentTypeHdr('multipart/alternative','spec','hdr') . self::EOL;
      $body .= self::EOL;
      $body .= self::GetMsgPart('spec');
      if (!empty(trim($this->MessageICal))) { $body .= self::EOL; $body .= self::GetIcsPart('spec'); }
      $body .= self::EOL;
      $body .= self::GetBoundary('spec','--');
      $body .= self::EOL;
      // inline
      $endInlineBoundary = '';
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'inline') {
          $body .= self::BuildAttachment($attachment,'body');
          $endInlineBoundary = self::GetBoundary('body','--');
        }
      }
      $body .= $endInlineBoundary;
      $body .= self::EOL;
      // attachment
      $endAttachBoundary = '';
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'attachment') {
          $body .= self::BuildAttachment($attachment,'wrap');
          $endInlineBoundary = self::GetBoundary('wrap','--');
        }
      }
      $body .= $endInlineBoundary;
      $body .= self::EOL;
    }
    // message with inline, attachment and ics
    elseif ($this->MessageType == 'attachment_ics_inline_message') {
      $body .= self::GetMsgPart('body');
      $body .= self::EOL;
      $body .= self::GetBoundary('body');
      // iCal
      if(!empty(trim($this->MessageICal))) {
        $allowed_methods = ['ADD','CANCEL','COUNTER','DECLINECOUNTER','PUBLISH','REFRESH','REPLY','REQUEST'];
        $method = "";
        $lines = explode("\n",$this->MessageICal);
        foreach ($lines as $line) {
          if (strpos($line,'METHOD:') !== false) {
            $line = str_replace(["\r",' '],'',$line);
            $bits = explode(':',$line);
            $method = strtoupper($bits[1]);
          }
        }
        if ($method != "" && in_array($method, $allowed_methods)) {
          $body .= self::GetBoundary('body');
          $body .= 'Content-Type: text/calendar; method='.$method . '; charset="' . $this->charset . '";' . self::EOL;
          $body .= 'Content-Transfer-Encoding: 7bit' . self::EOL;
          $body .= self::EOL;
          $body .= wordwrap($this->MessageICal, $this->wraplen) . self::EOL;
          $body .= self::EOL;
          $body .= self::GetBoundary('body');
        }
      }
      // attachment
      foreach ($this->attachments as $attachment) {
        if ($attachment[6] === 'attachment') {
          $body .= self::BuildAttachment($attachment,'wrap');
        }
      }
    }
    $body .= $gBEnd;
    return $body;
  }

  /**
   * Builds email header
   * @return string
   */
  private function BuildHeader() {
    $bcc  = ltrim(trim($this->bcc),',');
    $cc   = ltrim(trim($this->cc),',');
    $this->replyTo = ltrim(trim($this->replyTo),',');
    $this->sender  = ltrim(trim($this->sender),',');
    $hdr  = 'MIME-Version: 1.0' . self::EOL;
    $hdr .= 'X-Mailer: MailSend v'.self::VERSION . ' (phpmailer2.com)' . self::EOL;
    if ($this->Priority > 0) { $hdr .= 'X-Priority: ' . $this->Priority . self::EOL; }
    $hdr .= 'X-Originating-IP: '.$_SERVER['SERVER_ADDR'] . self::EOL;
    // custom headers
    for($index = 0; $index < count($this->custom_hdr); $index++) {
      $hdr .= trim($this->custom_hdr[$index][0]) . ': ' . trim($this->custom_hdr[$index][1]) . self::EOL;
    }
    $hdr .= 'Date: '.date('r', $_SERVER['REQUEST_TIME']) . self::EOL;
    $hdr .= 'Message-Id: <WRX' . md5((idate("U")-1000000000).uniqid()).'@send.a1ok>' . self::EOL;
    $hdr .= 'From: '.$this->sender . self::EOL;
    if(!empty(trim($this->replyTo))) {
      $hdr .= 'Reply-To: '.$this->replyTo . self::EOL;
      $hdr .= 'Return-Path: <'.self::GetEmailAddress($this->replyTo) . '>' . self::EOL;
    } else {
      $hdr .= 'Reply-To: ' . $this->sender . self::EOL;
      $hdr .= 'Return-Path: <'.self::GetEmailAddress($this->sender) . '>' . self::EOL;
    }
    if(!empty(trim($this->confirm_read))) {
      $hdr .= 'Disposition-Notification-To: ' . $this->confirm_read . self::EOL;
      $hdr .= 'Return-receipt-to: ' . $this->confirm_read . self::EOL;
    }
    if ($this->cc  != '') { $hdr .= 'Cc: ' .  $this->cc . self::EOL;  }
    if ($this->bcc != '') { $hdr .= 'Bcc: ' . $this->bcc . self::EOL; }
    if ($this->MessageType == 'message') {
      $hdr .= self::GetContentTypeHdr('multipart/alternative','wrap','hdr') . self::EOL;
    } elseif ($this->MessageType == 'inline' || $this->MessageType == 'message_inline') {
      $hdr .= self::GetContentTypeHdr('multipart/related','wrap','hdr') . self::EOL;
    } elseif ($this->MessageType == 'ics') {
      $hdr .= self::GetIcsPart('wrap','hdr');
    } else {
      $hdr .= self::GetContentTypeHdr('multipart/mixed','wrap','hdr') . self::EOL;
    }
    return $hdr;
  }

  /**
   * Clear all
   */
  public function Clear() {
    unset($this->cc);
    unset($this->bcc);
    unset($this->recipients);
    unset($this->attachments);
    unset($this->MessageICal);
    unset($this->MessageHTML);
    unset($this->MessageText);
  }

  /**
   * Sets the HTML message and returns modifications for inline images and backgrounds
   * will also set text message if it does not exist (can over ride)
   * @param string $content content of the HTML message
   * @param string $basedir directory to the location of the images (relative to file)
   */
  public function Data2HTML($content, $basedir = '') {
    static::IsExploitPath($content);
    if (is_file($content)) {
      $thisdir = (dirname($content) != '') ? rtrim(dirname($content),'/') . '/' : '';
      $basedir = ($basedir == '') ? $thisdir : '';
      $content = file_get_contents($content);
    }
    /*
     * preg_match_all returns indexed array:
     * 0 = full match (tag sep filename)
     * 1 = tag (src or background)
     * 2 = object filename)
     */
    preg_match_all("/(src|background)=\"(.*)\"/Ui", $content, $images);
    if(isset($images[2])) {
      foreach($images[2] as $i => $url) {
        if (!preg_match('#^[A-z]+://#',$url)) {
          if ($basedir != '') { $url = rtrim($basedir,'/') . '/' . $url; }
          $filename  = basename($url);
          $directory = dirname($url);
          $cid       = 'cid:' . md5($filename);
          if ($directory == '.') { $directory = ''; }
          if (function_exists('mime_content_type')) { $mimeType = mime_content_type($url); } else { $mimeType = 'application/octet-stream'; }
          if ( strlen($directory) > 1 && substr($directory,-1) != '/') { $directory .= '/'; }
          static::IsExploitPath($directory.$filename);
          static::IsExploitPath($url);
          if ( self::AddEmbeddedImage($directory.$filename, md5($filename), $filename, 'base64',$mimeType) ) {
            $content = preg_replace("/".$images[1][$i]."=\"".preg_quote($images[2][$i], '/')."\"/Ui", $images[1][$i]."=\"".$cid."\"", $content);
          }
        }
      }
    }
    $this->MessageHTML = $content;
  }

  /**
   * Creates the boundary line / end boundary line
   * @param string $type = wrap, body, spec, none
   * @param string $end (optional, triggers adding two dashes at end)
   * @return string (boundary line)
   */
  private function GetBoundary($type,$end='') {
    return '--' . $this->boundary[$type] . $end . self::EOL;
  }

  /**
   * Creates the Content-Type directive for the header
   * type = multipart/mixed / multipart/related / multipart/alternative
   * bkey = boundary (wrap / body / spec)
   * @return string (content type line)
   */
  private function GetContentTypeHdr($type,$bkey,$what='') {
    if ($what=='hdr') {
      $data = "Content-Type: " . $type . ";" . "\n";
      return $data . "\t" . 'boundary="' . $this->boundary[$bkey] . '"';
    }
    $data = "Content-Type: " . $type . ";" . "\n";
    return $data . "\t" . 'boundary="' . $this->boundary[$bkey] . '"';
  }

  /**
   * Builds ICS/iCalendar portion of message
   * @return string
   */
  private function GetIcsPart($boundary,$hdr='') {
    if(!empty(trim($this->MessageICal))) {
      $data = '';
      $allowed_methods = ['ADD','CANCEL','COUNTER','DECLINECOUNTER','PUBLISH','REFRESH','REPLY','REQUEST'];
      $method = "";
      $lines = explode("\n",$this->MessageICal);
      foreach ($lines as $line) {
        if (strpos($line,'METHOD:') !== false) {
          $line = str_replace(["\r",' '],'',$line);
          $bits = explode(':',$line);
          $method = strtoupper($bits[1]);
        }
      }
      if ($method != "" && in_array($method, $allowed_methods)) {
        $dhdr  = 'Content-Type: text/calendar; method='.$method . '; charset="' . $this->charset . '";' . self::EOL;
        $dhdr .= 'Content-Transfer-Encoding: 7bit' . self::EOL;
        if ($hdr == '') {
          if ($boundary != 'none') {
            $data .= '--'.$this->boundary[$boundary] . self::EOL;
            $data .= $dhdr;
          }
          $data .= self::EOL;
          $data .= wordwrap($this->MessageICal, $this->wraplen) . self::EOL;
          $data .= self::EOL;
          return $data;
        }
        return $dhdr;
      }
    }
    return;
  }

  /**
   * Builds plain text and HTML portion of message
   * @return string
   */
  private function GetMsgPart($bkey) {
    $data  = '';
    $data .= self::GetBoundary($bkey);
    $data .= self::GetContentTypeBody('text/plain','charset="' . $this->charset . '"','7bit');
    $data .= self::EOL;
    $wrapText = '';
    if (trim($this->MessageText) != '') {
      $wrapText = wordwrap($this->MessageText, $this->wraplen);
    }
    $data .= $wrapText . self::EOL;
    if (trim($this->MessageHTML) != '') {
      $data .= self::GetBoundary($bkey);
      $data .= self::GetContentTypeBody('text/html','charset="' . $this->charset . '"','base64') . self::EOL;
      $data .= self::EOL;
      $data .= base64_encode($this->MessageHTML) . self::EOL;
    }
    return $data;
  }

  /**
   * Creates the Content-Type directive for the body
   * @param string $type = multipart/mixed / multipart/related / multipart/alternative
   * @param string $charset
   * @param string $encoding
   * @param string $cid (optional)
   * @return string (content type line)
   */
  private function GetContentTypeBody($type,$charset,$encoding,$cid='') {
    $data  = 'Content-Type: ' . $type . ';' . self::EOL;;
    $data . "\t" . $charset . self::EOL;
    $data .= 'Content-Transfer-Encoding: ' . $encoding . self::EOL;
    if ($cid != '') {
      $data .= 'Content-ID: <' . $cid . '>' . self::EOL;
    }
    return $data;
  }

  /**
   * Gets email message type
   * @return string
   */
  private function getMsgType($type='') {
    if (is_string($type) && $type != '') {
      $type = rtrim($type,'_') . '_';
      $type = explode('_',$type);
    } else {
      $type = [];
    }
    if (!in_array('message',$type) && ($this->MessageHTML != '' || $this->MessageText != '')) {
      $type[] = 'message';
    }
    foreach ($this->attachments as $attachment) {
      if ($attachment[6] === 'inline') {
        if (!in_array('inline',$type)) {
          $type[] = 'inline';
        }
      }
    }
    foreach ($this->attachments as $attachment) {
      if ($attachment[6] === 'attachment') {
        if (!in_array('attachment',$type)) {
          $type[] = 'attachment';
        }
      }
    }
    if (!in_array('ics',$type) && $this->MessageICal != '') {
      $type[] = 'ics';
    }
    sort($type);
    $this->MessageType = implode('_',$type);
  }

  /**
   * Check file path for possible exploits and vulnerabilities.
   *
   * @param string $path Relative or absolute path to a file
   * @return bool
   */
  protected static function IsExploitPath($path) {
    // exploits: LFI/File manipulation, Directory traversal, File disclosure, Encoding, RCE
    $na_protocol = ['data:','file:','glob:','phar:','php:','zip:','..'];
    foreach ($na_protocol as $type) {
      if (stripos($path, $type) !== false) {
        exit('Unable to execute.<br>'.$this->EOL);
      }
    }
    return false;
  }

  /**
   * Checks string for multibyte characters
   * @param $str string
   * @return boolean (true if multibyte)
   */
  private function IsMultibyte($str) {
    return (mb_strlen($str) != strlen($str)) ? true : false;
  }

  /**
   * Check if file path is safe (real, accessible, not executable).
   *
   * @param string $path Relative or absolute path to a file
   * @return bool
   */
  protected static function IsPathSafe($path) {
    if (static::IsExploitPath($path)) { return false; }
    if (is_file($path)) { $path = str_replace(basename($path),'',$path); }
    $realPath = str_replace(rtrim($_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['PHP_SELF']),'/').'/','',realpath($path));
    if (strpos($path,'/')) { $realPath = rtrim($realPath,'/').'/'; }
    if (($path === false) || (strcmp($path, $realPath) !== 0)) { return false; }
    return (file_exists($path) && is_readable($path) && is_dir($path)) ? true: false;
  }

  /**
   * Prevent attacks by disallowing unsafe shell characters.
   * Modified version (Thanks to Paul Buonopane <paul@namepros.com>)
   * @param  string  $string (the string to be tested for shell safety)
   * @return bool
   */
  protected static function IsShellSafe($str) {
    if ( (empty(trim($str))) ||
         (escapeshellcmd($str) !== $str) ||
         (!in_array(escapeshellarg($str), ["'{$str}'","\"{$str}\""])) ||
         (preg_match('/[^a-zA-Z0-9@_\\-.]/', $str) !== 0)
       ) { return false; }
    return true;
  }

  /**
   * Validate email
   * @param string $email
   * @return boolean
   */
  public function IsValidEmail($email) {
    return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) ? true : false;
  }

  /**
   * Encodes and wraps long multibyte strings for mail headers
   * without breaking lines within a character.
   * Will validate $str as multibyte
   * @param string $str multi-byte string to encode
   * @return string
   */
  function MS_Mb_Encode($str,$len=75) {
    if (!self::IsMultibyte($str)) { return $str; }
    $cwrx = 'aj';
    $nlen = $len + strlen($cwrx) + 2;
    return str_replace($cwrx . ': ','',str_replace("\n ","\n",iconv_mime_encode($cwrx,self::SafeStr($str),["line-length"=>$nlen])));
  }

  /**
   * Converts to an associative array
   * from string, indexed array, (validates) associative array, and mixed
   * @param mixed  $param The object to convert
   * @param string $var2  Exists to convert old style 2 var function (email,name)
   */
  private function ObjectToArray($param,$var2='') {
    // OLD STYLE two-var conversion
    if ($var2 != '') {
      $param = [ $param => $var2];
    }
    // string - convert to assoc array
    elseif (is_string($param)) {
      if (strpos($param,',') !== false) {
        // string with multiple email addresses (separated by comma)
        $param = explode(',',$param);
      } else {
        // single email address (string)
        $param = [ $param => '' ];
      }
    }
    // indexed array
    elseif (is_array($param) && array_keys($param) === range(0, count($param) - 1)) {
      $new_array = [];
      foreach ($param as $key => $value) {
        if (is_array($value)) {
          foreach ($value as $subkey => $subval) {
            if (is_numeric($subkey)) {
              $new_array[$subval] = '';
            } else {
              $new_array[$subkey] = $subval;
            }
          }
        } else {
          $new_array = $new_array + self::ObjectToArray($value);
        }
      }
      $param = $new_array;
    }
    // associative array
    else {
      $new_array = [];
      foreach ($param as $key => $value) {
        if (!is_numeric($key)) {
          $new_array[$key] = $value;
        } else {
          $new_array = $new_array + self::ObjectToArray($value);
        }
      }
      $param = $new_array;
    }
    $new_array = [];
    foreach ($param as $key => $val) {
      $new_array = $new_array + self::ReorderArray([$key=>$val]);
    }
    return $param;
  }

  /**
   * Returns a properly structured email/name array
   * Orders as Email first, Name second (name could be blank)
   * @return array
   */
  private function ReorderArray($param) {
    $data = [];
    if (!is_array($param) && trim($param) != '') { /* this is a string */
      $parts = explode(',',$param);
      foreach ($parts as $element) {
        $element = trim($element);
        $data = $data + [ $element => '' ];
      }
      return $data;
    } elseif (is_array($param)) { /* this is an array */
      foreach ($param as $key => $val) {
        $key = trim($key);
        $val = trim($val);
        if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
          $data = [ $key => $val ];
        } elseif (filter_var($val, FILTER_VALIDATE_EMAIL)) {
          $data = [ $val => $key ];
        }
      }
      return $data;
    }
    return false;
  }

  /**
   * Filter data (ascii and url-encoded) to prevent header injection
   * @param string $str String
   * @return string (trimmed)
   */
  public function SafeStr($str) {
    return trim(str_ireplace([ "\r","\n","%0d","%0a","Content-Type:","bcc:","to:","cc:"],"",$str));
  }

  /**
   * Send the email
   * @return boolean.
   */
  public function Send() {
    if ($this->useSMTP === true && self::Send_SMTP()) {
      return true;
    }
    if (empty(trim($this->sender)) || empty(trim($this->recipients))) { return false; }
    self::getMsgType($this->MessageType);
    $body = self::BuildBody();
    $hdr  = self::BuildHeader();
    $ret  = false;
    if ($this->SendmailPath != '') {
      $opt = [ 0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w") ];
      $command = sprintf("%s -f %s", escapeshellcmd($this->SendmailPath), escapeshellarg($this->sender));
      $hndl = proc_open($command, $opt, $data);
      if (is_resource($hndl)) {
        $hdr = str_replace('(phpmailer2.com)','(phpmailer2.com) - proc',$hdr);
        fwrite($data[0], $hdr);
        fwrite($data[0], "To: " . $this->recipients . self::EOL);
        fwrite($data[0], "Subject: " . self::MS_Mb_Encode($this->Subject) . self::EOL);
        fwrite($data[0], $body . self::EOL);
        fclose($data[0]);
        fclose($data[1]);
        proc_close($hndl);
        $ret = true;
      }
      if ($ret !== true) {
        // proc_open did not work, try popen
        $hndl = popen($command,"w") or die("Couldn't Open Sendmail");
        if (is_resource($hndl)) {
            $hdr  = str_replace('(phpmailer2.com)','(phpmailer2.com) - popn',$hdr);
          fwrite($data[0], $hdr . self::EOL);
          fwrite($hndl, "To: " . $this->recipients . self::EOL);
          fwrite($hndl, "Subject: " . self::MS_Mb_Encode($this->Subject) . self::EOL);
          fwrite($hndl, $body . self::EOL);
          pclose($hndl);
          $ret = true;
        }
      }
    }
    if ($ret !== true) {
      // proc_open and popen both did not work, default to mail()
      $hdr = str_replace('(phpmailer2.com)','(phpmailer2.com) - mail',$hdr);
      $ret = mail($this->recipients, $this->Subject, $body, $hdr, $this->addparams);
    }
    return $ret;
  }

  /* SMTP transport ONLY
   * all security to ALL the data and email addresses
   * must occur BEFORE calling this function
   */
  public function Send_SMTP() {
    if (empty(trim($this->sender)) || empty(trim($this->recipients))) { return false; }
    self::getMsgType($this->MessageType);
    $body = self::BuildBody();
    $hdr  = self::BuildHeader();
    $hdr .= 'Subject: ' . self::MS_Mb_Encode($this->Subject) . self::EOL;
    $hdr .= 'To: ' . $this->recipients . self::EOL . self::EOL;
    $hdr = str_replace('(phpmailer2.com)','(phpmailer2.com) - smtp',$hdr);
    self::SMTP_Connect();
    self::SMTP_Recipient($this->recipients_rt);
    self::SMTP_Data($hdr,$body);
    if($this->SMTP_KeepAlive == true) {
      self::SMTP_Reset();
    }
    return true;
  }

  /**
   * Set additional parameter for mail() function
   * @param string $parameter
   * @return void
   */
  public function SetAddParams($params) {
    $this->addparams = $params;
  }

  /**
   * Set plain text
   * @param string $content
   */
  public function SetBodyText($content) {
    $this->MessageText = $content;
  }

  /**
   * Set email address for confirm email is received
   * @param string $email The email address
   */
  public function SetConfirmReceipt($param) {
    $this->confirm_rcpt = self::AddrFormatRFC2822($param);
  }

  /**
   * Set email address for confirm email is read
   * @param string $email The email address
   */
  public function SetConfirmRead($param) {
    $this->confirm_read = self::AddrFormatRFC2822($param);
  }

  /*
   * Set Priority
   * @param integer $param - from 1 (highest) to 5 (lowest)
   * @return boolean
   */
  public function SetPriority($param) {
    return (!intval($param)) ? false : $this->Priority = intval($param);
  }

  /**
   * Set reply_to
   * @param string $email
   * @return boolean
   */
  public function SetReplyTo($email) {
    if (self::IsValidEmail($email) === true) {
      $this->reply_to = $email;
      return true;
    }
    return false;
  }

  /**
   * Set sender
   * @param string $email
   */
  public function SetSender($email) {
    $this->sender = self::AddrFormatRFC2822($email);
    $addy = key($email);
    $this->smtp_from = $addy;
  }

  /**
   * Set sendmail (ie. change to a different email server)
   * @param string $param the location of the sendmail alternate
   */
  public function SetSendmailPath($param) {
    $this->SendmailPath = escapeshellcmd(self::SafeStr($param));
  }

  /**
   * Set subject
   * @param string $subject The subject of the email
   */
  public function SetSubject($subject) {
    $this->Subject = self::MS_Mb_Encode($subject);
  }

  /**
   * Uses SMTP transport by default, set to false to use Sendmail as default
   * @param bool
   */
  public function useSMTP($param=true) {
    $this->useSMTP = ($param === true) ? true : false;
  }

  /* END - METHODS ************/

  /* SMTP METHODS ************/

  /**
   * Sets SMTP Account (Username and password)
   * @return mixed
   */
  public function SetSMTPAccount($array) {
    $pwd   = trim(reset($array)); // password
    $uname = (is_numeric(key($array))) ? $pwd : trim(key($array)); // username
    $this->smtp_user = $uname;
    $this->smtp_pass = $pwd;
  }

  /**
   * Set SMTP host
   * @param string $param
   */
  public function SetSMTPhost($param) {
    $this->smtp_host = escapeshellcmd(self::SafeStr($param));
  }

  /**
   * Set SMTP port
   * @param integer $param
   */
  public function SetSMTPport($param) {
    $this->smtp_port = escapeshellcmd(self::SafeStr($param));
  }

  /**
   * Set SMTP password
   * @param string $param
   */
  public function SetSMTPpass($param) {
    //$this->smtp_pass = escapeshellcmd(self::SafeStr($param));
    $this->smtp_pass = $param;
  }

  /**
   * Set SMTP username
   * @param string $param
   */
  public function SetSMTPuser($param) {
    $this->smtp_user = escapeshellcmd(self::SafeStr($param));
  }

  /**
   * Connect to the server
   * return code: 220 success
   * @return bool
   */
  public function SMTP_Connect() {
    // check if already connected
    if ($this->SMTP_Stream) {
      return false;
    }
    // check for host
    if (isset($this->SMTP_Host) && $this->SMTP_Host != '') {
      if (self::GetMailServer($this->SMTP_Host,'is_valid') === false) {
        exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: invalid SMTP server.<br>' . self::EOL);
      }
      $host_name  = $this->SMTP_Host;
      $server_arr = [$this->SMTP_Host];
    } else {
      $host_name  = $this->SMTP_Domain;
      $server_arr = [$this->SMTP_Domain];
    }
    // check for port
    if (isset($this->SMTP_Port) && $this->SMTP_Port != '') {
      $srv_ports  = [$this->SMTP_Port];
    } else {
      $srv_ports  = [587,25,2525];
    }
    // connect to the smtp server
    $connect_options = $this->SMTP_Options;
    $create_options  = (!empty($connect_options)) ? stream_context_create($connect_options) : null;
    foreach ($server_arr as $host) {
      if (!isset($code) || $code != '220') {
        foreach ($srv_ports as $port) {
          if (function_exists('stream_socket_client')) {
            $this->SMTP_Stream = @stream_socket_client($host.':'.$port, $errno, $errstr, self::TIMEVAL, STREAM_CLIENT_CONNECT, $create_options);
          } else {
            $this->SMTP_Stream = @fsockopen($host,$port,$errno,$errstr, self::TIMEVAL);
          }
          if (!$this->SMTP_Stream) { return false; }
          $code = self::SMTP_GetResponse(['220'], 'CONNECT (' . $host.':'.$port.')');
          if ($code == '220') { $this->SMTP_Host = $host; break; }
        }
      } else {
        break;
      }
    }
    // set the time out
    stream_set_timeout($this->SMTP_Stream, self::TIMEVAL);

    // send EHLO command
    fwrite($this->SMTP_Stream, 'EHLO ' . $this->SMTP_Host . self::EOL);
    self::SMTP_GetResponse(['250'], 'EHLO');

    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: not connected to SMTP server.<br>' . self::EOL);
    }

    // send STARTTLS command
    fwrite($this->SMTP_Stream, 'STARTTLS' . self::EOL);
    $test = self::SMTP_GetResponse(['220'], 'STARTTLS');

    // initiate secure tls encryption
    $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) { $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT; }
    elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT')) { $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT; }
    elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT')) { $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT; }
    stream_socket_enable_crypto($this->SMTP_Stream, true, $crypto_method);

    // resend EHLO after tls negotiation
    fwrite($this->SMTP_Stream, 'EHLO ' . $this->SMTP_Host . self::EOL);
    self::SMTP_GetResponse(['250'], 'EHLO');

    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: not connected to SMTP server.<br>' . self::EOL);
    }

    if ( (isset($this->SMTP_Username) && $this->SMTP_Username != '') && (isset($this->SMTP_Username) && $this->SMTP_Username != '') ) {
      // Authenticate
      fwrite($this->SMTP_Stream,'AUTH LOGIN' . self::EOL);
      self::SMTP_GetResponse(['334'], 'AUTH LOGIN');
      // Send encoded username
      fwrite($this->SMTP_Stream, base64_encode($this->SMTP_Username) . self::EOL);
      self::SMTP_GetResponse(['334'], 'USER');
      // Send encoded password
      fputs($this->SMTP_Stream, base64_encode($this->SMTP_Password) . self::EOL);
      self::SMTP_GetResponse(['235'], 'PASS');
    }

    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: not connected to SMTP server.<br>' . self::EOL);
    }

    // send MAIL FROM command
    fwrite($this->SMTP_Stream,"MAIL FROM: <" . $this->SMTP_From . ">" . (($this->SMTP_Useverp) ? "XVERP" : "") . self::EOL);
    self::SMTP_GetResponse(['250'], 'MAIL FROM');

    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: not connected to SMTP server.<br>' . self::EOL);
    }
    return true;
  }

  /**
   * Sends header and message to SMTP Server
   * return code: 250 success (possible 251, have to allow for this)
   * @return bool
   */
  public function SMTP_Data($hdr,$body) {
    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: not connected to SMTP server.<br>' . self::EOL);
    }
    // initiate DATA stream
    fwrite($this->SMTP_Stream,"DATA" . self::EOL);
    self::SMTP_GetResponse(['354'], 'DATA');
    // send the header
    fwrite($this->SMTP_Stream, $hdr . self::EOL);
    // send the message
    fwrite($this->SMTP_Stream, $body . self::EOL);
    // end DATA stream
    fwrite($this->SMTP_Stream,'.' . self::EOL);
    self::SMTP_GetResponse(['250'], 'END');

    return true;
  }

  /**
   * Get response code returned by SMTP server
   * @return string
   */
  private function SMTP_GetResponse($expected_code, $command='') {
    $line = $data = '';
    $cmd  = ($command != '') ? '' . $command . ' - ' : '';

    while (substr($line, 3, 1) != ' ') {
      $line = stream_get_line($this->SMTP_Stream, 1000000, "\n");
      $data  .= $line;
      if (!$line) {
        exit(self::FAILMK . $cmd . ' Error while fetching server response.<br>' . self::EOL);
      }
    }
    if (!in_array(substr($line, 0, 3), $expected_code)) {
      exit(self::FAILMK . $cmd . ' Unable to send e-mail, error: "'.$line.'"<br>' . self::EOL);
    }
    if ($this->SMTP_Debug > 0) {
      $code     = substr($line, 0, 3);
      $thisCode = substr($data, 0, 4);
      $data = str_replace($thisCode,' | ',$data);
      $data = str_replace($code.' ','',$data);
      $data = ltrim($data,' | ');
      $data = $thisCode . $data;
      $debug_text = ($this->SMTP_Debug > 0) ? ' (' . $data . ')' : '';
      // put any response into response_array
      switch ($command) {
        case strstr($command,'CONNECT'):
          $this->SMTP_fdbk[] = self::PASSMK . 'Connection established' . $debug_text . '<br>' . self::EOL;
          break;
        case 'AUTH':
          $this->SMTP_fdbk[] = self::PASSMK . 'Authentication initiated' . $debug_text . '<br>' . self::EOL;
          break;
        case 'DATA':
          $this->SMTP_fdbk[] = self::PASSMK . 'Data transfer initiated' . $debug_text . '<br>' . self::EOL;
          break;
        case 'EHLO':
          $this->SMTP_fdbk[] = self::PASSMK . 'Connection &amp; replies verified' . $debug_text . '<br>' . self::EOL;
          break;
        case 'END':
          $this->SMTP_fdbk[] = self::PASSMK . 'Message transfer accepted' . $debug_text . '<br>' . self::EOL;
          break;
        case 'HELO':
          $this->SMTP_fdbk[] = self::PASSMK . 'Connection &amp; replies verified' . $debug_text . '<br>' . self::EOL;
          break;
        case 'MAIL FROM':
          $this->SMTP_fdbk[] = self::PASSMK . 'MAIL FROM sent and accepted' . $debug_text . '<br>' . self::EOL;
          break;
        case 'PASS':
          $this->SMTP_fdbk[] = self::PASSMK . 'Password accepted' . $debug_text . '<br>' . self::EOL;
          break;
        case 'QUIT':
          $this->SMTP_fdbk[] = self::PASSMK . 'Email transfer completed and connection closed' . $debug_text . '<br>' . self::EOL;
          break;
        case 'RCPT TO':
          $this->SMTP_fdbk[] = self::PASSMK . 'RCPT TO sent and accepted' . $debug_text . '<br>' . self::EOL;
          break;
        case 'STARTTLS':
          $this->SMTP_fdbk[] = self::PASSMK . 'STARTTLS initiated' . $debug_text . '<br>' . self::EOL;
          break;
        case 'USER':
          $this->SMTP_fdbk[] = self::PASSMK . 'Username accepted' . $debug_text . '<br>' . self::EOL;
          break;
        default:
          $this->SMTP_fdbk[] = 'unknown: ' . $command . $debug_text . '<br>' . self::EOL;
      }
    }
    return substr($line, 0, 3);
  }

  /**
   * Returns true if connected to a server otherwise false
   * @access public
   * @return bool
   */
  public function SMTP_IsStreamConnected() {
    if (!empty($this->SMTP_Stream)) {
      $status = socket_get_status($this->SMTP_Stream);
      if ($status["eof"]) {
        fclose($this->SMTP_Stream);
        $this->SMTP_Stream = 0;
        exit(self::FAILMK . 'SMTP connection error, aborting.<br>' . self::EOL);
        return false;
      }
      return true;
    }
    return false;
  }

  /**
   * Sends QUIT to SMTP Server then closes the stream
   * return code: 221 success
   * @return bool
   */
  public function SMTP_Quit() {
    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Critical error: not connected to SMTP server.<br>' . self::EOL);
    }
    // send the quit command to the server
    fwrite($this->SMTP_Stream,"quit" . self::EOL);
    self::SMTP_GetResponse(['221'], 'QUIT');
    // close the connection and reset the stream value
    if (!empty($this->SMTP_Stream)) {
      fclose($this->SMTP_Stream);
      $this->SMTP_Stream = 0;
    }
    return true;
  }

  /**
   * Sends smtp command RCPT TO
   * Returns true if recipient (email) accepted (false if not accepted).
   * return code: 250 success (possible 251, have to allow for this)
   * @return bool
   */
  public function SMTP_Recipient($param) {
    if( strpos($param, ',') !== false ) {
      $emails = explode(',',$param);
      foreach ($emails as $email) {
        fwrite($this->SMTP_Stream,"RCPT TO: <" . trim($email) . ">" . self::EOL);
        $code = self::SMTP_GetResponse(['250','251'], 'RCPT TO');
      }
    } else {
      fwrite($this->SMTP_Stream,"RCPT TO: <" . trim($param) . ">" . self::EOL);
      $code = self::SMTP_GetResponse(['250','251'], 'RCPT TO');
    }
    return;
  }

  /* Send RSET (aborts any transport in progress and keeps connection alive)
   * Implements RFC 821: RSET <EOL>
   * return code 250 success
   * @return bool
   */
  public function SMTP_Reset() {
    if (!self::SMTP_IsStreamConnected()) {
      exit(__LINE__ . ' ' . self::FAILMK . 'Called SMTP_KeepAlive without connection.<br>' . PHPMailer2::EOL);
    }
    fwrite($this->SMTP_Stream,"RSET" . PHPMailer2::EOL);
    $code = self::SMTP_GetResponse(['250'], 'RSET');
    return true;
  }

  private function GetEmailAddress($param) {
    if (is_string($param)) {
      $param = explode(' ',$param);
    }
    $name  = reset($param);
    $email = key($param);
    $var1 = str_replace(['<','>'],'',$email);
    $var2 = str_replace(['<','>'],'',$name);
    if (strstr($var1,'=?utf-8?B?')) {
      $var1 = str_replace('=?utf-8?B?','',$var1);
      $var1 = str_replace('?=','',$var1);
      $var1 = base64_decode($var1);
    }
    if (strstr($var2,'=?utf-8?B?')) {
      $var2 = str_replace('=?utf-8?B?','',$var2);
      $var2 = str_replace('?=','',$var2);
      $var2 = base64_decode($var2);
    }
    if (filter_var($var1, FILTER_VALIDATE_EMAIL)) {
      return $var1;
    } elseif (filter_var($var2, FILTER_VALIDATE_EMAIL)) {
      return $var2;
    }
    return false;
  }

  /**
   * dual use method
   * 1- with only $url passed, either a host or path (string) and returns the MX record domain name
   * 2- with a fully qualified mail server passed, returns true/false if an MX record matches
   * @param string $url
   * @param string $validate
   * @return string (mail server) (if $validate is 'no_test' and mail server found)
   * @return bool (if no mail server found)
   */
  private function GetMailServer($url='',$validate='no_test') {
    if ($url == '') { $url = $_SERVER['SERVER_NAME']; }
    $bits = parse_url($url);
    if (isset($bits['host'])) { $key = 'host'; } elseif (isset($bits['path'])) { $key = 'path'; }
    $tld = $bits[$key];
    if ($validate === 'is_valid') { $tld = $url; }
    if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $tld, $match)) {
      getmxrr($match['domain'],$mx_details);
      if (is_array($mx_details) && count($mx_details)>0) {
        if ($validate === 'is_valid') {
          if ($url == reset($mx_details)) {
            return true;
          }
        } else {
          return reset($mx_details);
        }
      }
    }
    return false;
  }
  /* END - SMTP METHODS ************/
}
?>
