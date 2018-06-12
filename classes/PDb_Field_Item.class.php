<?php

/*
 * class for handling the display of fields in a template
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2018 xnau webdesign
 * @license    GPL2
 * @version    1.1
 * @link       http://xnau.com/wordpress-plugins/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Field_Item extends PDb_Form_Field_Def {

  /**
   * @var string the field's value
   */
  private $value = '';
	
	/**
   *
   * @var int the id of the current record
   */
	private $record_id = 0;
	
	/**
   *
   * @var string the instantiating module
   */
	private $module;

  /**
   *
   * @var string the element class name
   */
  private $field_class;

  /**
   *
   * @var string the href value
   */
  private $link = '';

  /**
   * @var bool determines if the field value is output as HTML or a formatted value 
   */
  public $html_output = true;

  /**
   * 
   * @param array|object|string $config the field attributes or field name
   * @param int|string $id the id of the source record if available
   */
  public function __construct( $config, $id = false )
  {
    /*
     * OK, this is going to be instantiated by: 
     *    an object with at the very least a 'name' property naming a defined PDB field
     */
// error_log( __CLASS__ . ' instantiated with: ' . print_r( $field,1)  . '
//      
//trace: '.print_r(  wp_debug_backtrace_summary(),1)  );
    
    if ( is_string( $config ) ) {
      $config = (object) array('name' => $config);
    }
    
    parent::__construct($config->name);

    if ( $id )
      $this->record_id = $id;

    // load the object properties
    $this->assign_props( $config );
    
  }
  
  /**
   * provides direct access to property values
   * 
   * this is provided for compatibility
   * 
   * @param string $name of the property to get
   * @return string|int|array
   */
  public function __get( $name )
  {
    if ( property_exists( $this, $name ) ) {
      return $this->{$name};
    }
    return parent::__get($name);
  }
  
  /**
   * handles isset call on class properties
   * 
   * @param string $name of the property
   */
  public function __isset( $name )
  {
    switch ( $name ) {
      case 'record_id':
        return $this->record_id > 0;
      default:
        return $this->{$name} !== '';
    }
  }

  // template methods

  /**
   * prints a field label
   */
  public function print_label()
  {
    echo $this->_label();
  }

  /**
   * prints a field value, wrapping with a link as needed
   * 
   */
  public function print_value( $print = true )
  {
    if ( $print ) {
      echo $this->get_value_display();
    } else {
      return $this->get_value_display();
    }
  }

  /**
   * returns a field value, wrapping with a link as needed
   * 
   * @return string the field's value, prepped for display
   * 
   */
  public function get_value_display()
  {
    return PDb_FormElement::get_field_value_display( $this, $this->html_output );
  }
  
  /**
   * supplies the raw value of the field
   * 
   * @return string|int value
   */
  public function value()
  {
    return $this->value;
  }

  /**
   * provides the dynamic value for a dynamic hidden field
   *
   * @return string
   */
  public function dynamic_value()
  {
    $value = '';
    if ( $this->is_dynamic_hidden_field() ) {
      $value = Participants_Db::get_dynamic_value( $this->default );
    }
    return $value;
  }
  
  /**
   * supplies the value in a displayable format
   * 
   * @return string
   */
  public function display_array_value()
  {
    if ( $this->is_value_set() ) {
      $titles = array();
      foreach ( xnau_FormElement::field_value_array($this->value) as $value ) {
        $titles[] = $this->value_title( $value );
      }
      return esc_html( implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $titles ) );
    }
    return $this->value();
  }
  
  /**
   * provides the current value of the field as an associative array or string
   * 
   * @return array|string the current value of the field
   */
  public function get_value()
  {
    if ( $this->is_value_set() ) {
      return $this->make_assoc_value_array( xnau_FormElement::field_value_array($this->value) );
    }
    return $this->value;
  }
  
  /**
   * supplies the attributes array
   * 
   * @return array value
   */
  public function attributes()
  {
    return $this->attributes;
  }
  
  /**
   * supplies the name of the current module
   * 
   * @return string value
   */
  public function module()
  {
    return $this->module;
  }
  
  /**
   * supplies the href value
   * 
   * @return string value
   */
  public function link()
  {
    return $this->link;
  }
  
  /**
   * sets the field's value
   * 
   * @param string|int|array $value
   */
  public function set_value( $value )
  {
    $this->value = $value;
  }
  
  /**
   * sets the field's module
   * 
   * @param string $module
   */
  public function set_module( $module )
  {
    $this->module = $module;
  }


  /**
   * sets the link value of the object
   * 
   * @param string $url the link url
   */
  public function set_link( $url )
  {
    $this->link = $url;
  }
  
  /**
   * sets the current record id
   * 
   * @param int  $record_id of the pdb record
   */
  public function set_record_id( $record_id )
  {
    $this->record_id = $record_id;
  }

  /**
   * tells if the title (field label) is empty
   * 
   * @return bool true if there is a title string defined
   */
  public function has_title()
  {
    return strlen( $this->title ) > 0;
  }
  
  /**
   * tells if the field has a non-empty value
   * 
   * alias of has_content()
   * 
   * @return bool
   */
  public function has_value()
  {
    return $this->has_content();
  }
  
  /**
   * tells if the link property is set
   * 
   * @return bool
   */
  public function has_link()
  {
    return $this->link !== '';
  }

  /**
   * tests a value for emptiness, includinf arrays with empty elements
   * 
   * @param mixed $value the value to test
   * @return bool
   */
  public function is_empty( $value = false )
  {
    if ( $value === false ) {
      $value = $this->value;
    }

    if ( is_object( $value ) ) {
      // backward compatibility: we used to call this with an object
      $value = $value->value;
    }

    if ( is_array( $value ) )
      $value = implode( '', $value );

    return strlen( $value ) === 0;
  }

  /**
   * tests a field for printable content
   * 
   * @return bool
   */
  public function has_content()
  {
    switch( $this->form_element ) {
      case 'link':
        $value = $this->link;
        break;
      default:
        $value = $this->value;
    }
    return !$this->is_empty($value);
  }
  
  /**
   * tells if the field's value is diferent from the field's default value
   * 
   * @return bool true if the value is different from the default
   */
  public function is_not_default()
  {
    return $this->value !== $this->default_value();
  }

  /**
   * sets the html mode
   * 
   * @param bool $mode tur to use html, false to use ony formatted values
   */
  public function html_mode( $mode )
  {
    $this->html_output = (bool) $mode;
  }

  /**
   * assigns the object properties that match properties in the supplied object
   * 
   * @param object $config the field data object
   */
  protected function assign_props( $config )
  {
    // assigns the dynamic contextual properties
    if ( property_exists( $config, 'value' ) ) {
      $this->set_value( $config->value );
    }
    if ( property_exists( $config, 'record_id' ) ) {
      $this->set_record_id( $config->record_id );
    }
    if ( property_exists( $config, 'module' ) ) {
      $this->set_module( $config->module );
    }
    $this->set_link_field_value();
    
    if ( $this->is_valid_single_record_link_field() ) {
      $this->set_link( Participants_Db::single_record_url( $this->record_id ) );
    }
  }

  /**
   * assigns the values for the special case of the "link" field 
   */
  private function set_link_field_value()
  {
    if ( $this->form_element === 'link' ) {
      $parts = (array) maybe_unserialize( $this->value );
      if ( isset( $parts[0] ) && filter_var( $parts[0], FILTER_VALIDATE_URL ) ) {
        $this->link = $parts[0];
      }
      $this->value = $this->has_link() ? ( isset( $parts[1] ) ? $parts[1] : $this->default ) : '';
    }
  }

  /**
   * tells if the field is valid to get the single record link
   * 
   * @return bool
   */
  private function is_valid_single_record_link_field()
  {
    return (
            !in_array( $this->module, array('single', 'signup') ) &&
            $this->is_single_record_link_field() &&
            $this->record_id
            );
  }

  /**
   * outputs a single record link
   *
   * @param string $template an optional template for showing the link
   *
   * @return string the HTML for the single record link
   *
   */
  public function output_single_record_link( $template = false )
  {
    $pattern = $template ? $template : '<a class="single-record-link" href="%1$s" title="%2$s" >%2$s</a>';
    $url = Participants_Db::single_record_url( $this->record_id );

    return sprintf( $pattern, $url, (empty( $this->value ) ? $this->default : $this->value ) );
  }

  /**
   * tells if the field should have the required field marker added to the label
   * 
   * 
   * @return bool true if the marker should be added
   */
  public function place_required_mark()
  {
    /**
     * @filter pdb-add_required_mark
     * @param bool
     * @param PDb_Field_Item
     * @return bool
     */
    return Participants_Db::apply_filters( 'add_required_mark', Participants_Db::$plugin_options['mark_required_fields'] && $this->validation != 'no' && !in_array( $this->module, array('list', 'search', 'total', 'single') ), $this );
  }

  /**
   * prints a CSS class name based on the form_element
   */
  public function print_element_class()
  {

    // for compatibility we are not prefixing the form element class name
    echo PDb_Template_Item::prep_css_class_string( $this->form_element );

    if ( $this->is_readonly() )
      echo ' readonly-element';
  }

  /**
   * prints a CSS classname based on the field name
   */
  public function print_element_id()
  {
    echo PDb_Template_Item::prep_css_class_string( Participants_Db::$prefix . $this->name ); 
  }

  /**
   * prints the field element with an id
   * 
   */
  public function print_element_with_id()
  {
    $this->attributes['id'] = PDb_Template_Item::prep_css_class_string( Participants_Db::$prefix . $this->name );
    $this->print_element();
  }

  /**
   * prints the field element
   *
   */
  public function print_element()
  {
    $this->field_class = ( $this->validation != 'no' ? "required-field" : '' ) . ( in_array( $this->form_element(), array('text-line', 'date', 'timestamp') ) ? ' regular-text' : '' );

    /**
     * @filter pdb-before_display_form_input
     * 
     * allows a callback to alter the field object before displaying a form input element
     * 
     * @param PDb_Form_Element this instance
     */
    Participants_Db::do_action( 'before_display_form_input', $this );

    if ( $this->is_readonly() && !in_array( $this->form_element(), array('captcha') ) ) {

      if ( !in_array( $this->form_element(), array('rich-text') ) ) {

        $this->attributes['readonly'] = 'readonly';
        $this->_print();
      } else {
        echo '<span class="pdb-readonly ' . $this->field_class . '" >' . $this->get_value_display() . '</span>';
      }
    } else {

      $this->_print();
    }
  }

  /**
   * prints the element
   */
  public function _print()
  {
    PDb_FormElement::print_element( array(
        'type' => $this->form_element(),
        'value' => $this->value(),
        'name' => $this->name(),
        'options' => $this->options(),
        'class' => $this->field_class,
        'attributes' => $this->attributes(),
        'module' => $this->module(),
        'link' => $this->link(),
            )
    );
  }

  /**
   * tells if the help_text is defined
   */
  public function has_help_text()
  {
    return !empty( $this->help_text );
  }

  /**
   * prints the field's help text
   */
  public function print_help_text()
  {
    echo $this->prepare_display_value( PDb_Template_Item::html_allowed( $this->help_text ) );
  }

  /**
   * returns a field's error status
   *
   * @return mixed bool false if no error, string error type if validation error is set
   *
   */
  public function has_error()
  {

    $error_array = array('no error');

    if ( is_object( Participants_Db::$validation_errors ) )
      $error_array = Participants_Db::$validation_errors->get_error_fields();

    if ( $error_array and isset( $error_array[$this->name] ) )
      return $error_array[$this->name];
    else
      return false;
  }

  /**
   * adds the required marker to a field label as needed
   *
   */
  private function _label()
  {

    $label = $this->prepare_display_value( PDb_Template_Item::html_allowed( $this->title ) );

    if ( $this->place_required_mark() ) {

      $label = sprintf( Participants_Db::$plugin_options['required_field_marker'], $label );
    }

    return $label;
  }
  
  /**
   * prepare a field for display
   *
   * makes the value available to translations
   * 
   * @param string $string the value to be prepared
   */
  private function prepare_display_value( $string )
  {   
    return Participants_Db::apply_filters('translate_string', $string);
  }
  
  /**
   * provides an associative array of values
   * 
   * this will add an "other" value if no matching field option is found
   * 
   * @param array $value_list
   * @return array as $title => $value
   */
  private function make_assoc_value_array( $value_list )
  {
    $title_array = array();
    $other_list = array();
    
    foreach( $value_list as $value )
    {
      if ( in_array( $value, $this->options ) ) {
        $title_array[ $this->value_title($value) ] = $value;
      } else {
        $other_list[] = $value;
      }
    }
    if ( ! empty( $other_list ) ) {
      return array_merge( $title_array, array('other' => implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $other_list ) ) );
    } else {
      return $title_array;
    }
  }

}
