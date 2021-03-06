<?php
namespace Electro\Plugins\MatisseComponents;

use Matisse\Components\Base\Component;
use Matisse\Components\Base\HtmlComponent;
use Matisse\Components\Text;
use Matisse\Exceptions\ComponentException;
use Matisse\Parser\Expression;
use Matisse\Properties\Base\HtmlComponentProperties;
use Matisse\Properties\TypeSystem\is;
use Matisse\Properties\TypeSystem\type;

class FieldProperties extends HtmlComponentProperties
{
  /**
   * @var string Appends the value to `controlClass` preceded by a space.
   */
  public $addControlClass = '';
  /**
   * Bootstrap form field grouo addon
   *
   * @var string
   */
  public $append = type::content;
  /**
   * @var bool Autofocus the field?
   */
  public $autofocus = false;
  /**
   * A view-model-based path reference for binding the field to the corresponding model property.
   *
   * > <p>**Ex:** `model=model.name`
   *
   * <p>When set, the {@see name} property is overritten, so you should not specify it.
   *
   * <p>When {@see multilang} = true, for each enabled locale, the corresponding generated field will bind to this
   * property's expression appended with `_lang`, where `lang` is the language code.
   *
   * @var string
   */
  public $bind = '';
  /**
   * @var string
   */
  public $controlClass = 'form-control';
    /**
   * @var string If not empty and the field's value is empty, the later will be set to this value.
   */
  public $defaultValue = type::string; //allow 'field[]'
/**
   * Adds the specified class(es) to the inherited `class` prop.
   *
   * @var string
   */
  public $fieldClass = 'form-group';
  /**
   * @var string
   */
  public $groupClass = '';
  /**
   * @var string An icon CSS class name.
   */
  public $icon = '';
  /**
   * @var string
   */
  public $label = '';
  /**
   * @var string Creates label after the Input to work fine with floating-label used in Material Admin Design.
   */
  public $labelAfterInput = false;
  /**
   * @var string
   */
  public $labelClass = '';
  /**
   * @var string Language code (ex: pt-PT).
   */
  public $lang = '';
  /**
   * @var array A list of localization records.
   * <p>See {@see Electro\Localization\Services\Locale::getAvailableExt()}.
   */
  public $languages = type::data;
  /**
   * @var bool Is it a ultilingual field?
   */
  public $multilang = false;
  /**
   * @var string The field name, used on the form submission.
   */
  public $name = '';
  /**
   * Bootstrap form field grouo addon
   *
   * @var \Matisse\Components\Metadata|null
   */
  public $prepend = type::content;
  /**
   * @var bool
   */
  public $readOnly = false;
  /**
   * @var bool
   */
  public $required = false;
  /**
   * @var string The field type, when no child components are specfied.
   */
  public $type = [
    'text', is::enum, [
      'text', 'line', 'multiline', 'password', 'date', 'time', 'datetime', 'number', 'color', 'hidden',
      'url', 'email', 'tel', 'range', 'search', 'month', 'week', 'checkbox', 'radiobutton', 'switch',
    ],
  ];
  /**
   * @var integer|null Maximum allowed length. Only for text input fields.
   */
  public $maxLength = type::number;
}

/**
 * Wraps one or more form field components with HTML to create a formatted form field.
 *
 * <p>This is Bootstrap-compatible. It comes pre-configured to generated markup for a vertical form field layout.
 * <p>It is also compatible with other CSS frameworks, as long as it is properly configured.
 *
 * <p>This component also supports generating multi-language fields, where multiple inputs are generated for each field,
 * one for each language; only one of them is visible at one time.
 *
 * <p>Field has support for generating fields with add-ons. An add-on can be an icon, button, checkbox, etc, and it can
 * be left or right aligned.
 */
class Field extends HtmlComponent
{
  const allowsChildren = true;

  const propertiesClass = FieldProperties::class;

  /** @var FieldProperties */
  public $props;

  function setupFirstRun ()
  {
    if (!$this->hasChildren ()) {
      switch ($this->props->type) {
        case 'checkbox':
          $child = Checkbox::create ($this);
          break;
        case 'radiobutton':
          $child = RadioButton::create ($this);
          break;
        case 'switch':
          $child = Switch_::create ($this);
          break;
        default:
          $child = Input::create ($this, [
            'type' => $this->props->type,
          ]);
      }
      $child->props->autofocus = $this->props->autofocus;
      $this->addChild ($child);
    }
    foreach ($this->getChildren () as $child)
      if ($child instanceof HtmlComponent) {
        // Skip the Input[type=color] component.
        if ($child instanceof Input && $child->props->type == 'color')
          continue;
        $child->props->class = enum (' ', $this->props->controlClass, $this->props->addControlClass);
      }
  }

  protected function init ()
  {
    parent::init ();
    if ($this->props->multilang) {
      // Update labels on language selectors of mulilingual form input fields.
      $this->context->getAssetsService ()->addInlineScript (<<<JS
selenia.on ('languageChanged', function (lang) {
  function focusMultiInput (e) {
    $ (e).next().children('input:visible,textarea:visible').eq(0).focus();
  }

  if ($ ('.form-control-line + .input-group-btn button .lang').length >0)
	  $ ('.form-control-line + .input-group-btn button .lang')
		.add ('textarea[lang] + .form-control-line + .input-group-btn button .lang')
		.html (lang.substr (-2));
});
JS
        , 'initFieldMulti');
    }
  }

  protected function preRender ()
  {
    $this->cssClassName = $this->props->fieldClass;
    parent::preRender ();
  }


  protected function render ()
  {
    $prop = $this->props;

    $inputFlds = $this->getClonedChildren ();
    if (empty ($inputFlds))
      throw new ComponentException($this, "<b>Field</b> component must define <b>one or more</b> child component instances.",
        true);

    // Treat the first child component specially

    /** @var Component $input */
    $input   = $inputFlds[0];
    $append  = $this->getChildren ('append');
    $prepend = $this->getChildren ('prepend');

    $fldId = $input->props->get ('id', $prop->name);

    if ($fldId) {
      $fldId = $fldId . '-'. $this->renderCount;

      foreach ($inputFlds as $counter => $c)
        if ($c->isPropertySet ('hidden') && !$c->getComputedPropValue ('hidden')) break;

      // Special case for the HtmlEditor component.

      if ($input->className == 'HtmlEditor') {
        $forId = $fldId . "-{$counter}_field";
        $click = "$('#{$fldId}-{$counter} .redactor_editor').focus()";
      }

      // All other component types with an ID set.
      else {
        $forId = $fldId . "-$counter";
        $click = $prop->multilang ? "focusMultiInput(this)" : null;
      }
    }
    else $forId = $click = null;

    if ($input->className == 'Input') {
      if ($prop->type && !$input->props->type)
        $input->props->type = $prop->type;
      switch ($input->props->type) {
        case 'date':
        case 'time':
        case 'datetime':
          $btn    = Button::create ($this, [
            'class'    => 'btn btn-default',
            'icon'     => 'glyphicon glyphicon-calendar',
            'script'   => "$('#{$fldId}-0').data('DateTimePicker').show()",
            'tabIndex' => -1,
          ]);
          $append = [$btn];
      }
    }

    if (exists ($prop->icon))
      $append = [Text::from ($this->context, "<i class=\"$prop->icon\"></i>")];

    $this->beginContent ();

    if (!$this->props->labelAfterInput) {
      // Output a LABEL
      $label = $prop->label;
      if (!empty($label))
        $this->tag ('label', [
          'class'   => enum (' ', $prop->labelClass, $prop->required ? 'required' : ''),
          'for'     => $forId,
          'onclick' => $click,
        ], $label);
    }

    // Output child components

    $hasGroup = $append || $prepend || $prop->groupClass || $prop->multilang;
    if ($hasGroup)
      $this->begin ('div', [
        'id'    => "$forId-group",
        'class' => enum (' ', when ($append || $prepend || $prop->multilang, 'input-group'), $prop->groupClass),
      ]);
    $this->beginContent ();

    if ($prepend)
      $this->renderAddOns ($prepend);

    if ($prop->multilang)
      foreach ($inputFlds as $i => $input)
        foreach ($prop->languages as $lang)
          $this->outputField ($input, $i, $fldId, $prop->name, $lang);
    else
      foreach ($inputFlds as $i => $input)
        $this->outputField ($input, $i, $fldId, $prop->name);

    if ($append) $this->renderAddOns ($append);

    $shortLang = substr ($prop->lang, -2);

    if ($prop->multilang)
      echo html ([
        h ('span.input-group-btn', [
          h ('button.btn.btn-default.dropdown-toggle', [
            "id"            => "langMenuBtn_$forId",
            'type'          => "button",
            'data-toggle'   => "dropdown",
            'aria-haspopup' => "true",
            'aria-expanded' => "false",
          ], [
            h ('i.fa.fa-flag'),
            h ('span.lang', $shortLang),
            h ('span.caret'),
          ]),
          h ("ul.dropdown-menu.dropdown-menu-right", [
            'id'              => "langMenu_$forId",
            "aria-labelledby" => "langMenuBtn_$forId",
          ],
            map ($prop->languages, function ($l) use ($forId) {
              return h ('li', [
                h ('a', [
                  'tabindex' => "1",
                  'href'     => "javascript:selenia.setLang('{$l['name']}','#$forId-group')",
                ], $l['label']),
              ]);
            })),
        ]),
      ]);

    if ($this->props->labelAfterInput) {
      // Output a LABEL
      $label = $prop->label;
      if (!empty($label))
        $this->tag ('label', [
          'class'   => enum (' ', $prop->labelClass, $prop->required ? 'required' : ''),
          'for'     => $forId,
          'onclick' => $click,
        ], $label);
    }

    if ($hasGroup)
      $this->end ();
  }

  private function outputField ($input, $i, $id, $name, $langR = null)
  {
    if ($bind = $this->props->bind)
      $name = str_segmentsFirst ($bind, '|');
    $lang = $langR ? $langR['name'] : '';
    //TODO: $lang = str_replace ('-', '_', $lang);
    $_lang = $lang ? "_$lang" : '';
    $name  = "$name$_lang";

    /** @var InputProperties $prop */
    $prop = $input->props;

    // EMBEDDED COMPONENTS

    if ($input instanceof HtmlComponent) {
      /** @var HtmlComponent $input */

      if ($id)
        $prop->id = "$id-$i$_lang";
      // note: can't use dots, as PHP would replace them by underscores when loading the form fields
      $prop->name = str_replace ('.', '/', $name);
      if (!$i)
        $input->originalCssClassName = $input->cssClassName;
      if ($lang)
        $input->htmlAttrs['lang'] = $lang;
      if ($this->props->required && $prop->defines ('required'))
        $prop->required = true;
      if ($this->props->readOnly && $prop->defines ('readOnly'))
        $prop->readOnly = true;
      if ($this->props->maxLength && $prop->defines ('maxLength'))
        $prop->maxLength = $this->props->maxLength;
      if (exists ($this->props->defaultValue))
        $prop->defaultValue = $this->props->defaultValue;

      if ($bind) {
        $valuefield = $prop->defines ('testValue') ? 'testValue' : 'value';
        $x          = explode ('.', $name);
        if (count ($x) > 1)
          $name = "$x[0].'".implode ("'.'", array_slice ($x, 1) )."'";
        else
          $name = "this.'$name'";
        $input->addBinding ($valuefield, new Expression ("{{$name}}"));
      }
    }
    $input->run ();
  }

  /**
   * @param Component[] $addOns
   */
  private function renderAddOns (array $addOns)
  {
    $prev = '';
    foreach ($addOns as $addOn) {

      switch ($addOn->getTagName ()) {
        case 'Text':
        case 'Literal':
        case 'Checkbox':
        case 'RadioButton':
          if ($prev != 'addon') {
            if ($prev) echo '</span>';
            echo '<span class="input-group-addon">';
            $prev = 'addon';
          }
          $addOn->run ();
          break;
        case 'Button':
          if ($prev != 'btn') {
            if ($prev) echo '</span>';
            echo '<span class="input-group-btn">';
            $prev = 'btn';
          }
          $addOn->run ();
          break;
      }
    }
    if ($prev)
      echo '</span>';
  }
}

