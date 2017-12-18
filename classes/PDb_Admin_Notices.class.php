<?php
/**
 * handles feedback messages in the admin
 * 
 * see the API calls for usage
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2017  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       https://www.alexgeorgiou.gr/persistently-dismissible-notices-wordpress/
 * @depends    
 */
defined( 'ABSPATH' ) || die( '-1' );

class PDb_Admin_Notices {

  /**
   *
   * @var PDb_Admin_Notices the class instance 
   */
  private static $_instance;

  /**
   *
   * @var object  the current notices
   */
  private $admin_notice_list;

  /**
   * @var string list of message types
   */
  const TYPES = 'error,warning,info,success';

  /**
   * @var string  the option name
   */
  const pdb_admin_notice = 'pdb_admin_notices';

  /**
   * @var string  GET var key
   */
  const get_key = 'pdb_admin_notices_dismiss';

  /**
   * these are our API calls
   * 
   * the static calls are the most likely to be used, dynamic calls are for situations 
   * where the object needs to be set up and modified before posting the message.
   * 
   * @param string  $message can inclue HTML, remember it is wrapped in a <p> tag.
   * @param string  $context a context message (goes in the message heading)
   * @param bool    $persistent if true, the message persists across page loads until dismissed
   * 
   * @return string unique id for the notice
   */
  public static function post_error( $message, $context = '', $persistent = true )
  {
    $notice = self::get_instance();
    return $notice->error( $message, $context, $persistent );
  }

  public function error( $message, $context = '', $persistent = true )
  {
    return $this->notice( 'error', $message, $context, $persistent );
  }

  public static function post_warning( $message, $context = '', $persistent = true )
  {
    $notice = self::get_instance();
    return $notice->warning( $message, $context, $persistent );
  }

  public function warning( $message, $context = '', $persistent = true )
  {
    return $this->notice( 'warning', $message, $context, $persistent );
  }

  public static function post_success( $message, $context = '', $persistent = true )
  {
    $notice = self::get_instance();
    return $notice->success( $message, $context, $persistent );
  }

  public function success( $message, $context = '', $persistent = true )
  {
    return $this->notice( 'success', $message, $context, $persistent );
  }

  public static function post_info( $message, $context = '', $persistent = true )
  {
    $notice = self::get_instance();
    return $notice->info( $message, $context, $persistent );
  }

  public function info( $message, $context = '', $persistent = true )
  {
    return $this->notice( 'info', $message, $context, $persistent );
  }
  
  /**
   * deletes the named notice
   * 
   * @param string  $id the notice id
   */
  public static function delete_notice( $id )
  {
    $notice = self::get_instance();
    $notice->delete($id);
  }

  /**
   * 
   */
  private function __construct()
  {
    $this->admin_notice_list = get_option( self::pdb_admin_notice, array() );
    $this->purge_transient_notices();

    add_action( 'admin_init', array($this, 'action_admin_init'), 20 );
    add_action( 'admin_notices', array($this, 'action_admin_notices') );
    add_action( 'admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts') );
    add_action( 'participants_database_uninstall', array($this, 'uninstall') );
  }

  /**
   * provides a class instance
   * 
   * @return PDb_Admin_Notices a class instance
   */
  public static function get_instance()
  {
    if ( !( self::$_instance instanceof self ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * initialize the class functionality
   */
  public function action_admin_init()
  {
    $message_id = filter_input( INPUT_GET, self::get_key, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE );
    if ( $message_id ) {
      $this->delete( $message_id );
      wp_die();
    }
  }

  public function action_admin_enqueue_scripts()
  {
    if ( $this->is_plugin_screen() ) {
      wp_enqueue_script( Participants_Db::$prefix . 'admin-notices' );
    }
  }

  /**
   * fired on admin_notices action
   */
  public function action_admin_notices()
  {
    if ( $this->is_plugin_screen() ):
      
      foreach ( $this->admin_notice_list as $admin_notice ) {

        $dismiss_url = add_query_arg( array(
            self::get_key => $admin_notice->id
                ), admin_url() );
        ?><div
          class="notice pdb_admin_notices-notice notice-<?php
          echo $admin_notice->type;

          echo ' is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
          ?>">
          <h4><?php echo Participants_Db::$plugin_title . $admin_notice->context ?>:</h4>
          <p><span class="dashicons <?php echo $this->dashicon( $admin_notice->type ) ?>"></span>&nbsp;<?php echo $admin_notice->message ?></p>

        </div><?php
      }
    endif;
  }

  /**
   * removes a notice from the notices option
   * 
   * @param string  $notice_id
   */
  public function delete( $notice_id )
  {
    if ( isset( $this->admin_notice_list[$notice_id] ) ) {
      unset( $this->admin_notice_list[$notice_id] );
      $this->update_notices();
    }
  }

  /**
   * updates the notices option
   */
  private function update_notices()
  {
    update_option( self::pdb_admin_notice, $this->admin_notice_list );
  }

  /**
   * purges all transient notices
   */
  private function purge_transient_notices()
  {
    $this->admin_notice_list = array_filter( $this->admin_notice_list, function ($notice) {
      return $notice->persistent;
    } );
  }

  /**
   * tells if the current admin screen is a plugin screen
   * 
   * @return bool
   * 
   */
  private function is_plugin_screen()
  {
    $page = get_current_screen();
    return stripos( $page->id, Participants_Db::PLUGIN_NAME ) !== false;
  }

  /**
   * provides the dashicons icon text
   * 
   * @param string $type message type
   * @return string
   */
  private function dashicon( $type )
  {
    switch ( $type ) {
      case 'warning':
      case 'error':
        return 'dashicons-warning';
      case 'success':
        return 'dashicons-yes';
      case 'info':
      default:
        return 'dashicons-info';
    }
  }

  /**
   * adds the notice
   * 
   * @param string  $type
   * @param string  $message
   * @param string  $context a context string fro the message header
   * @param bool    $persistent if true, message will persis across page loads
   */
  private function notice( $type, $message, $context, $persistent )
  {
    $notice = new pdb_admin_notice_message($type,$message,$context,$persistent);

    $this->admin_notice_list[$notice->id] = $notice;
    $this->update_notices();
    return $notice->id;
  }

  /**
   * prints a PHP error in the admin message
   * 
   * @param int $errno
   * @param string $errstr
   * @param string $errfile
   * @param string $errline
   * @param string $errcontext
   * @return boolean
   */
  public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext )
  {
    if ( !( error_reporting() & $errno ) ) {
      // This error code is not included in error_reporting
      return;
    }

    $message = "errstr: $errstr, errfile: $errfile, errline: $errline, PHP: " . PHP_VERSION . " OS: " . PHP_OS;

    $self = self::get_instance();

    switch ( $errno ) {
      case E_USER_ERROR:
        $self->error( $message );
        break;

      case E_USER_WARNING:
        $self->warning( $message );
        break;

      case E_USER_NOTICE:
      default:
        $self->notice( $message );
        break;
    }

    // write to wp-content/debug.log if logging enabled
    error_log( $message );

    // Don't execute PHP internal error handler
    return true;
  }

  /**
   * clears the options on uninstall
   * 
   * @global wpdb $wpdb
   */
  public function uninstall()
  {
    global $wpdb;
    $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE "' . self::pdb_admin_notice . '%";' );
  }

}

/**
 * models a single admin notice
 */
class pdb_admin_notice_message {
  /**
   * @var string the type of message
   */
  private $type;
  
  /**
   * @var string the messgae text
   */
  private $message;
  
  /**
   * @var string the context string
   */
  private $context;
  
  /**
   * @var bool whether the message should be persistent across page loads
   */
  private $persistent;
  
  /**
   * @var string holds the unique id for the message
   */
  private $id;
  
  /**
   * @var string provides a joining string for using the context string in the message heading
   */
  private $context_joiner = '/';
  
  /**
   * instantiates the message
   * 
   * 
   * @param string  $type
   * @param string  $message
   * @param string  $context a context string fro the message header
   * @param bool    $persistent if true, message will persist across page loads
   */
  public function __construct( $type, $message, $context, $persistent)
  {
    $this->type = $type;
    $this->message = $message;
    $this->context = $context;
    $this->persistent = $persistent;
    $this->notice_id( $message );
  }
  
  /**
   * provides object property values
   * 
   * @param string $name property name
   * @return mixed
   */
  public function __get( $name )
  {
    switch ( $name ) {
      case 'id':
        return $this->id;
      case 'message':
        return wp_kses_post( $this->message );
      case 'context':
        return esc_html( $this->context_string() );
      case 'persistent':
        return (bool) $this->persistent;
      case 'type':
        return $this->type;
    } 
  }
  
  /**
   * provides a context string
   * 
   * @return string
   */
  private function context_string()
  {
    return empty( $this->context ) ? '' : $this->context_joiner . $this->context;
  }
  /**
   * provides a unique ID for each message
   * 
   * @param string $message the message
   * @return string
   */
  private function notice_id( $message )
  {
    $current_user = wp_get_current_user();
    $this->id = $current_user->ID . '-' . hash( 'crc32', $message );
  }
}