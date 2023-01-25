<?php
namespace MRBS\Auth;

use MRBS\DBFactory;
use MRBS\User;

class AuthDbExt extends Auth
{
  protected $db_ext_conn;

  protected $db_table;
  protected $password_format;
  protected $column_name_username;
  protected $column_name_display_name;
  protected $column_name_password;
  protected $column_name_email;
  protected $column_name_level;

  public function __construct()
  {
    global $auth;

    if (empty($auth['db_ext']['db_system']))
    {
      $auth['db_ext']['db_system'] = 'mysql';
    }

    // Establish a connection
    $persist = 0;
    $port = isset($auth['db_ext']['db_port']) ? (int)$auth['db_ext']['db_port'] : null;

    $this->db_ext_conn = DBFactory::create(
        $auth['db_ext']['db_system'],
        $auth['db_ext']['db_host'],
        $auth['db_ext']['db_username'],
        $auth['db_ext']['db_password'],
        $auth['db_ext']['db_name'],
        $persist,
        $port
      );

    // Take our own copies of the settings
    $vars = array(
        'db_table',
        'password_format',
        'column_name_username',
        'column_name_display_name',
        'column_name_password',
        'column_name_email',
        'column_name_level',
        'use_md5_passwords'
      );

    foreach ($vars as $var)
    {
      $this->$var = (isset($auth['db_ext'][$var])) ? $auth['db_ext'][$var] : null;
    }

    // Backwards compatibility setting
    if (!isset($this->password_format) && !empty($auth['db_ext']['use_md5_passwords']))
    {
      $this->password_format = 'md5';
    }
  }


  /* validateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The user name
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public function validateUser(?string $user, ?string $pass)
  {
    $retval = false;

    // syntax_casesensitive_equals() modifies our SQL params array for us.   We need an exact match -
    // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '

    $sql_params = array();

    $query = "SELECT " . $this->db_ext_conn->quote($this->column_name_password) .
             "FROM " . $this->db_ext_conn->quote($this->db_table) .
             "WHERE " . $this->db_ext_conn->syntax_casesensitive_equals($this->column_name_username,
                                                                        $user,
                                                                        $sql_params);

    $stmt = $this->db_ext_conn->query($query, $sql_params);

    if ($stmt->count() == 1) // force a unique match
    {
      $row = $stmt->next_row();

      switch ($this->password_format)
      {
        case 'md5':
          if (md5($pass) == $row[0])
          {
            $retval = $user;
          }
          break;

        case 'sha1':
          if (sha1($pass) == $row[0])
          {
            $retval = $user;
          }
          break;

        case 'sha256':
          if (hash('sha256', $pass) == $row[0])
          {
            $retval = $user;
          }
          break;

        case 'crypt':
          $recrypt = crypt($pass,$row[0]);
          if ($row[0] == $recrypt)
          {
            $retval = $user;
          }
          break;

        case 'password_hash':
          if (password_verify($pass, $row[0]))
          {
            // Should we call password_needs_rehash() ?
            // Probably not as we may not have UPDATE rights on the external database.
            $retval = $user;
          }
          break;

        default:
          // Otherwise assume plaintext
          if ($pass == $row[0])
          {
            $retval = $user;
          }
          break;
      }
    }

    return $retval;
  }


  public function getUser(string $username) : ?User
  {
    global $auth;

    static $users = array();  // Cache results for performance

    if (!array_key_exists($username, $users))
    {
      $sql_params = array();

      // Only retrieve the columns we need (a) to minimise the query and (b) to avoid
      // sending unnecessary information unencrypted over the internet (Remote SQL is
      // usually unencrypted).
      $columns = array(
          $this->column_name_display_name,
          $this->column_name_email,
          $this->column_name_level
        );

      $sql = "SELECT " . implode(', ', array_map(array($this->db_ext_conn, 'quote'), $columns)) . "
                FROM " . $this->db_ext_conn->quote($this->db_table) . "
               WHERE " . $this->db_ext_conn->syntax_casesensitive_equals($this->column_name_username,
                                                                         $username,
                                                                         $sql_params) . "
               LIMIT 1";

      $stmt = $this->db_ext_conn->query($sql, $sql_params);

      // The username doesn't exist - return NULL
      if ($stmt->count() === 0)
      {
        $users[$username] = null;
      }
      else
      {
        // The username does exist - return a User object
        $data = $stmt->next_row_keyed();

        $user = new User($username);

        // Set the email address
        if (isset($this->column_name_email) && isset($data[$this->column_name_email]))
        {
          $user->email = $data[$this->column_name_email];
        }

        // Set the display name
        if (isset($this->column_name_display_name) && isset($data[$this->column_name_display_name]))
        {
          $user->display_name = $data[$this->column_name_display_name];
        }

        // Set the level
        // First get the default level.  Any admins defined in the config
        // file override settings in the external database.
        $user->level = $this->getDefaultLevel($username);

        // Then if they are not an admin get their level from the external db
        if ($user->level < 2)
        {
          // If there's can entry in the db, then use that
          if (isset($this->column_name_level) &&
              ($this->column_name_level !== '') &&
              isset($data[$this->column_name_level]))
          {
            $user->level = $data[$this->column_name_level];
          }
        }

        // Then set the remaining properties. (We don't set all the properties from
        // $data initially because we want to preserve the default values if we don't
        // have data for the four important properties.)
        // (Note that normally there won't be any extra properties because we have
        // specified above the columns that we want, but this code is here so that extra
        // columns can be added if required.)
        foreach ($data as $key => $value)
        {
          if (!property_exists($user, $key))
          {
            $user->$key = $value;
          }
        }

        $users[$username] = $user;
      }
    }

    return $users[$username];
  }


  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames() : array
  {
    if (isset($this->column_name_display_name) && ($this->column_name_display_name !== ''))
    {
      $display_name_column = $this->column_name_display_name;
    }
    else
    {
      $display_name_column = $this->column_name_username;
    }

    $sql = "SELECT " . $this->db_ext_conn->quote($this->column_name_username) . " AS username, ".
                       $this->db_ext_conn->quote($display_name_column) . " AS display_name
            FROM " . $this->db_ext_conn->quote($this->db_table) . " ORDER BY display_name";

    $res = $this->db_ext_conn->query($sql);

    $users =  $res->all_rows_keyed();
    self::sortUsers($users);

    return $users;
  }
}
