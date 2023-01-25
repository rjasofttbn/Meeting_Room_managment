<?php

namespace MRBS\Session;

use MRBS\Form\FieldDiv;
use MRBS\Form\Form;
use MRBS\Form\ElementA;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\User;

use PDO;


// An abstract class for those session schemes that implement a login form
abstract class SessionWithLogin implements SessionInterface
{
  protected $form = array();


  public function __construct()
  {
    // Get non-standard form variables
    foreach (array('action', 'username', 'password', 'target_url', 'returl') as $var) {
      $this->form[$var] = \MRBS\get_form_var($var, 'string', null, INPUT_POST);
    }
  }

  // Gets the username and password.  Returns: Nothing
  //
  //    $target_url   The URL to go to after successful login
  //    $returl       The URL to return to eventually
  public function authGet(?string $target_url = null, ?string $returl = null, ?string $error = null, bool $raw = false): void
  {
    if (!isset($target_url)) {
      $target_url = \MRBS\this_page(true);
    }

    // Omit the Login link in the header when we're on the login page itself
    \MRBS\print_header(null, null, true);
    $action = \MRBS\multisite(\MRBS\this_page());
    $this->printLoginForm($action, $target_url, $returl, $error, $raw);
    exit;
  }

  abstract public function getCurrentUser(): ?User;

  // Returns the parameters ('method', 'action' and 'hidden_inputs') for a
  // Logon form.  Returns an array.
  public function getLogonFormParams(): ?array
  {
    return array(
      'action' => \MRBS\multisite('admin.php'),
      'method' => 'post',
      'hidden_inputs' =>  array(
        'target_url' => \MRBS\this_page(true),
        'action'     => 'QueryName'
      )
    );
  }


  // Returns the parameters ('method', 'action' and 'hidden_inputs') for a
  // logoff form.  Returns an array of parameters, or null if no form is to be
  // shown.
  public function getLogoffFormParams(): ?array
  {
    return array(
      'action' => \MRBS\multisite('admin.php'),
      'method' => 'post',
      'hidden_inputs' =>  array(
        'target_url' => \MRBS\this_page(true),
        'action'     => 'SetName',
        'username'   => '',
        'password'   => ''
      )
    );
  }


  public function processForm(): void
  {

    if (isset($this->form['action'])) {

      // Target of the form with sets the URL argument "action=QueryName".
      // Will eventually return to URL argument "target_url=whatever".
      if ($this->form['action'] == 'QueryName') {

        $this->authGet($this->form['target_url']);
        exit(); // unnecessary because authGet() exits, but just included for clarity
      }

      // Target of the form with sets the URL argument "action=SetName".
      // Will eventually return to URL argument "target_url=whatever".
      if ($this->form['action'] == 'SetName') {

        // First make sure the password is valid
        if (!isset($this->form['username']) || ($this->form['username'] == '')) {
          $this->logoffUser();
        } else {
          // If we're going to do something then check the CSRF token first.
          // (Don't check the token before logging off the user because if the session has
          // expired due to inactivity, the token will be invalid, but that won't matter because
          // the result will be the same anyway - logging off the user - and we avoid
          // generating an unnecessary CSRF error message.)
          Form::checkToken();

          // Get a valid user
          $valid_username = $this->getValidUser($this->form['username'], $this->form['password']);
          // print_r($this->form['username'].'-'.$this->form['password']); exit;
          // Successful login.   You can't get out of getValidUser() without a valid username and password
          $this->logonUser($valid_username);

          if (!empty($this->form['returl'])) {
            // check to see whether there's a query string already
            $this->form['target_url'] .= (\MRBS\utf8_strpos($this->form['target_url'], '?') === false) ? '?' : '&';
            $this->form['target_url'] .= 'returl=' . urlencode($this->form['returl']);
          }
        }

        \MRBS\location_header($this->form['target_url']); // Redirect browser to initial page
      }
    }
  }


  // Can only return a valid username.  If the username and password are not valid it will ask for new ones.
  protected function getValidUser(?string $username, ?string $password): string
  {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "mrbs";
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    date_default_timezone_set("Asia/Dhaka");

    $nname = $this->form['username'];
    $password = $this->form['password'];

    $result = $conn->prepare("SELECT id, `name`,`role`, `email`,password_hash from mrbs_users 
    where name ='$nname' and password_hash = '$password' and status = 1 ");

    $result->execute();
    $row = $result->fetch();

    

    if (empty($row)) {
      $this->authGet($this->form['target_url'], $this->form['returl'], \MRBS\get_vocab('unknown_user'));
      exit(); // unnecessary because authGet() exits, but just included for clarity
      exit;
    }

    if (isset($row['name'])) {
      $valid_username = $row['name'];
    }
   
    if ($row['name'] != $this->form['username']) {
      // if (($valid_username = \MRBS\auth()->validateUser($this->form['username'], $this->form['password'])) === false) {
      $this->authGet($this->form['target_url'], $this->form['returl'], \MRBS\get_vocab('unknown_user'));
      exit(); // unnecessary because authGet() exits, but just included for clarity
    }
  
    return $valid_username;
  }


  protected function logonUser(string $username): void
  {
  }


  public function logoffUser(): void
  {
  }


  // Displays the login form.
  // Will eventually return to $target_url with query string returl=$returl
  // If $error is set then an $error is printed.
  // If $raw is true then the message is not HTML escaped
  private function printLoginForm(string $action, ?string $target_url, ?string $returl, ?string $error = null, bool $raw = false): void
  {
    $form = new Form();
    $form->setAttributes(array(
      'class'  => 'standard',
      'id'     => 'logon',
      'method' => 'post',
      'action' => $action
    ));

    // Hidden inputs
    $hidden_inputs = array(
      'returl'     => $returl,
      'target_url' => $target_url,
      'action'     => 'SetName'
    );
    $form->addHiddenInputs($hidden_inputs);

    // Now for the visible fields
    if (isset($error)) {
      $p = new ElementP();
      $p->setText($error, false, $raw);
      $form->addElement($p);
    }

    $fieldset = new ElementFieldset();
    $fieldset->addLegend(\MRBS\get_vocab('please_login'));

    // The username field
    $tag = (\MRBS\auth()->canValidateByEmail()) ? 'enter_username' : 'users.name';
    $placeholder = \MRBS\get_vocab($tag);

    $field = new FieldInputText();
    $field->setLabel(\MRBS\get_vocab('user'))
      ->setLabelAttributes(array('title' => $placeholder))
      ->setControlAttributes(array(
        'id'           => 'username',
        'name'         => 'username',
        'placeholder'  => $placeholder,
        'required'     => true,
       // 'autofocus'    => true,
        'autocomplete' => 'off'
        // 'autocomplete' => 'username'
      ));
    $fieldset->addElement($field);

    // The password field
    $field = new FieldInputPassword();
    $field->setLabel(\MRBS\get_vocab('users.password'))
      ->setControlAttributes(array(
        'id'           => 'password',
        'name'         => 'password',
        'required'     => true,
        'autocomplete' => 'off'
        // 'autocomplete' => 'current-password'
      ));
    $fieldset->addElement($field);

    $form->addElement($fieldset);

    // The submit button
    $fieldset = new ElementFieldset();
    $field = new FieldInputSubmit();
    $field->setControlAttributes(array('value' => \MRBS\get_vocab('login')));
    $fieldset->addElement($field);

    $form->addElement($fieldset);

    if (\MRBS\auth()->canResetPassword()) {
      $fieldset = new ElementFieldset();
      $field = new FieldDiv();
      $a = new ElementA();
      // $a->setAttribute('href', \MRBS\multisite('reset_password.php'))
      //   ->setText(\MRBS\get_vocab('lost_password'));
      // $field->addControl($a);
      $fieldset->addElement($field);
      $form->addElement($fieldset);
    }

    $form->render();



    // Print footer and exit
    \MRBS\print_footer(true);
  }


  // Check we've got the right authentication type for the session scheme.
  // To be called for those session schemes which require the same
  // authentication type
  protected function checkTypeMatchesSession(): void
  {
    global $auth;

    if ($auth['type'] !== $auth['session']) {
      $class = get_called_class();
      $message = "MRBS configuration error: $class needs \$auth['type'] set to '" . $auth['session'] . "'";
      die($message);
    }
  }
}
