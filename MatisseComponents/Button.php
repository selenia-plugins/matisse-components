<?php
namespace Selenia\Plugins\MatisseComponents;

use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Attributes\VisualComponentAttributes;
use Selenia\Matisse\VisualComponent;

class ButtonAttributes extends VisualComponentAttributes
{
  public $action;
  public $param;
  public $script;
  public $url;
  public $label;
  public $message;
  public $confirm = false;
  public $help;
  public $tabIndex;
  public $icon;
  public $type    = 'button';

  protected function typeof_action () { return AttributeType::ID; }

  protected function typeof_param () { return AttributeType::TEXT; }

  protected function typeof_script () { return AttributeType::TEXT; }

  protected function typeof_url () { return AttributeType::TEXT; }

  protected function typeof_label () { return AttributeType::TEXT; }

  protected function typeof_message () { return AttributeType::TEXT; }

  protected function typeof_confirm () { return AttributeType::BOOL; }

  protected function typeof_help () { return AttributeType::TEXT; }

  protected function typeof_tabIndex () { return AttributeType::NUM; }

  protected function typeof_icon () { return AttributeType::TEXT; }

  protected function typeof_type () { return AttributeType::TEXT; }

  protected function enum_type () { return ['button', 'submit']; }
}

class Button extends VisualComponent
{

  public $cssClassName = 'btn';

  /** overriden */
  protected $containerTag = 'button';

  /**
   * Returns the component's attributes.
   * @return ButtonAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return ButtonAttributes
   */
  public function newAttributes ()
  {
    return new ButtonAttributes($this);
  }

  protected function preRender ()
  {
    if (isset($this->attrs ()->icon))
      $this->addClass ('with-icon');
    parent::preRender ();
  }

  protected function render ()
  {
    $attr       = $this->attrs ();
    $actionData = '';
    if ($attr->disabled)
      $this->addAttribute ('disabled', 'disabled');
    $this->addAttributeIf ($attr->tabIndex, 'tabindex', $attr->tabIndex);
    $this->addAttribute ('type', $attr->type);
    if ($this->page->browserIsIE)
      $this->addAttribute ('hideFocus', 'true');
    if (isset($attr->action)) {
      if (isset($attr->param))
        $action = $attr->action . ':' . $attr->param;
      else $action = $attr->action;
      //if ($this->page->browserIsIE) $actionData = "<!--$action-->";
      //else $this->addAttribute('value',$action);
      $this->beginAttribute ('onclick', null, ';');
      if ($attr->confirm)
        $this->addAttributeValue ("Button_onConfirm('{$action}','{$this->attrs()->message}')");
      else $this->addAttributeValue ("doAction('" . $action . "')");

      $this->endAttribute ();
    }
    else {
      if (isset($attr->script))
        $this->addAttribute ('onclick', $attr->script);
      else if (isset($attr->url))
        $this->addAttribute ('onclick', "go('{$this->attrs()->url}',event);");
    }
    if (isset($attr->help))
      $this->addAttribute ('title', $attr->help);

    $this->beginContent ();

    if (isset($attr->icon)) {
      $this->addTag ('i', [
        'class' => $attr->icon
      ]);
    }
    $txt = trim ($attr->label . $actionData);
    echo strlen ($txt) ? $txt : (isset($attr->icon) ? '' : '&nbsp;');

  }
}