<?php

namespace wdmg\widgets;

/**
 * Yii2 ReCaptcha
 *
 * @category        Widgets
 * @version         1.0.2
 * @author          Alexsander Vyshnyvetskyy <alex.vyshnyvetskyy@gmail.com>
 * @link            https://github.com/wdmg/yii2-recaptcha
 * @copyright       Copyright (c) 2019 - 2020 W.D.M.Group, Ukraine
 * @license         https://opensource.org/licenses/MIT Massachusetts Institute of Technology (MIT) License
 *
 */

use wdmg\helpers\ArrayHelper;
use Yii;
use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\base\InvalidConfigException;

class ReCaptcha extends InputWidget
{
    public $language = 'en'; // see https://developers.google.com/recaptcha/docs/language
    public $siteKey; // Your public sitekey
    public $apiURL; // The URL for reCaptcha API ('//www.google.com/recaptcha/api.js' or use alternative URL if necessary '//www.recaptcha.net/recaptcha/api.js')

    // Javascript callback`s for reCaptcha events
    public $callbacks = [
        'onload' => null,
        'success' => null,
        'expired' => null,
        'error' => null
    ];

    public $render = 'explicit'; // Optional. Whether to render the widget explicitly. Defaults to onload, which will render the widget in the first g-recaptcha tag it finds.
    public $theme; // Optional (`dark`, `light`). The color theme of the widget.
    public $type; // Optional (`image`, `audio`). The type of CAPTCHA to serve.
    public $badge; // Optional (`bottomright`, `bottomleft`, `inline`). Reposition the reCAPTCHA badge. 'inline' lets you position it with CSS.
    public $size; // Optional (`compact`, `normal`, `invisible`). The size of the widget. Use `invisible` value for create an invisible widget bound to a div and programmatically executed.
    public $tabIndex = 0; // Optional. The tabindex of the widget and challenge. If other elements in your page use tabindex, it should be set to make user navigation easier.
    public $isolated = false; // Optional. For plugin owners to not interfere with existing reCAPTCHA installations on a page. If true, this reCAPTCHA instance will be part of a separate ID space.

    public $options = []; // Default input options
    public $widgetOptions = []; // Default widget options

    private $_widgetClass = 'g-recaptcha'; // Default CSS classname for widget container

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->siteKey && isset(Yii::$app->params["recaptcha.siteKey"]))
            $this->siteKey = Yii::$app->params["recaptcha.siteKey"];

        if (!$this->apiURL && isset(Yii::$app->params["recaptcha.apiURL"]))
            $this->apiURL = Yii::$app->params["recaptcha.apiURL"];

        if (!$this->siteKey)
            throw new InvalidConfigException("Required widget param `siteKey` isn't set.");

        if (!$this->apiURL)
            throw new InvalidConfigException("Required widget param `apiURL` isn't set.");

        if (!$this->render)
            throw new InvalidConfigException("Required widget param `render` isn't set.");

        if (!in_array($this->render, ['explicit', 'onload']))
            throw new InvalidConfigException("The widget param `render` must be `explicit` or `onload`.");

        if (!is_array($this->callbacks))
            throw new InvalidConfigException("The widget param `callbacks` must be array.");

        if (!empty($this->theme) && !in_array($this->theme, ['dark', 'light']))
            throw new InvalidConfigException("The widget param `theme` must be empty or `dark`, `light`.");

        if (!empty($this->type) && !in_array($this->type, ['image', 'audio']))
            throw new InvalidConfigException("The widget param `type` must be empty or `image`, `audio`.");

        if (!empty($this->badge) && !in_array($this->badge, ['bottomright', 'bottomleft', 'inline']))
            throw new InvalidConfigException("The widget param `badge` must be empty or `bottomright`, `bottomleft`, `inline`.");

        if (!empty($this->size) && !in_array($this->size, ['compact', 'normal', 'invisible']))
            throw new InvalidConfigException("The widget param `size` must be empty or `compact`, `normal`, `invisible`.");

        if (!is_integer($this->tabIndex))
            throw new InvalidConfigException("The widget param `tabIndex` must be a integer.");

        if (!is_bool($this->isolated))
            throw new InvalidConfigException("The widget param `isolated` must be a boolean.");

        if ($this->isolated === true)
            $this->isolated = "true";
        else
            $this->isolated = "false";

    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        parent::run();
        $view = $this->view;

        // We form request parameters
        $query = [
            'render' => $this->render,
            'onload' => 'reCaptchaInit',
        ];

        if ($lang = $this->getLanguage())
            $query['hl'] = $lang;

        // Register the main reCaptcha JS script
        $view->registerJsFile(
            $this->apiURL . '?' . http_build_query($query),
            [
                'async' => true,
                'defer' => true,
                'position' => $view::POS_END,
            ]
        );

        // Register the JS initialization script in the frontend
        $this->registerClientJS();

        // Register the hidden field for subsequent validation
        $this->registerHiddenField();

        // Output a div container for recaptcha
        echo Html::tag('div', '', $this->getWidgetOptions());
    }

    /**
     * Returns options for further formation
     * recaptcha widget based on configuration parameters
     *
     * @return array
     */
    protected function getWidgetOptions() {

        $options = [];

        // Сollect options based on the configuration of the widget
        $options['data'] = [
            'sitekey' => (isset($this->siteKey)) ? $this->siteKey : false,
            'onload-callback' => (isset($this->callbacks['onload'])) ? $this->callbacks['onload'] : false,
            'callback' => (isset($this->callbacks['success'])) ? $this->callbacks['success'] : false,
            'expired-callback' => (isset($this->callbacks['expired'])) ? $this->callbacks['expired'] : false,
            'error-callback' => (isset($this->callbacks['error'])) ? $this->callbacks['error'] : false,
            'input-id' => $this->getWidgetId(),
            'form-id' => $this->getFormId(),
            'badge' => (isset($this->badge)) ? $this->badge : false,
            'theme' => (isset($this->theme)) ? $this->theme : false,
            'type' => (isset($this->type)) ? $this->type : false,
            'size' => (isset($this->size)) ? $this->size : false,
            'tabindex' => (isset($this->tabIndex)) ? $this->tabIndex : false
        ];

        // Glue the custom widget class with the required class name `.g-recaptcha`
        if (isset($this->widgetOptions['class'])) {
            $options['class'] = ArrayHelper::merge(explode(' ', $this->widgetOptions['class']), [$this->_widgetClass]);
        } else {
            $options['class'] = $this->_widgetClass;
        }

        // Set the correct link for label
        if ($this->size == 'invisible') {
            $this->field->label(false);
        } else {
            if ($label = $this->field->labelOptions) {
                $label['for'] = $this->getWidgetId();
            }
        }

        // Assign the widget ID
        $options['id'] = $this->getWidgetId().'-recaptcha';

        return $options;
    }

    /**
     * Returns widget ID
     *
     * @return mixed|string
     */
    protected function getWidgetId() {

        if (isset($this->widgetOptions['id']))
            return $this->widgetOptions['id'];

        if ($this->hasModel())
            return Html::getInputId($this->model, $this->attribute);

        return $this->getId() . '-' . strtolower($this->name);
    }

    /**
     * Returns the parent id of the form where
     * the widget was initialized
     *
     * @return null|string
     */
    protected function getFormId() {

        if ($form = $this->field->form) {
            if (isset($form->options['id']))
                return $form->options['id'];
            else
                return $form->id;
        }

        return null;
    }

    /**
     * Returns the language of the widget or sets the current
     * language used in the application as such
     *
     * @return null|string
     */
    protected function getLanguage() {

        $language = null;
        $exceptions = ['zh-CN', 'zh-TW', 'zh-TW'];

        if (is_string($this->language))
            $language = $this->language;
        elseif (is_string(Yii::$app->language))
            $language = Yii::$app->language;

        if (!is_null($language)) {

            if (strpos($language, '-') === false)
                return $language;

            if (in_array($language, $exceptions))
                return $language;

        }

        return null;
    }

    /**
     * Registers client JS in the system
     */
    protected function registerClientJS() {

        $view = $this->view;

        $view->registerJs(<<< JS
function reCaptchaInit() {
    "use strict";
    jQuery(".g-recaptcha").each(function () {
        const recaptcha = jQuery(this);
        if (recaptcha.data("recaptcha-client-id") === undefined) {
            
            if (recaptcha.data("onload-callback"))
                eval(recaptcha.data("onload-callback"));
            
            const clientId = grecaptcha.render(recaptcha.attr("id"), {
                "callback": function(response) {
                    
                    if (recaptcha.data("form-id") !== "")
                        jQuery("#" + recaptcha.data("input-id"), "#" + recaptcha.data("form-id")).val(response).trigger("change");
                    else
                        jQuery("#" + recaptcha.data("input-id")).val(response).trigger("change");
                    
                    if (recaptcha.data("size") == 'invisible') {
                        if (recaptcha.data("form-id")) {
                            jQuery("#" + recaptcha.data("form-id")).submit();
                        }
                    }
                    
                    if (recaptcha.data("callback"))
                        eval("(" + recaptcha.data("callback") + ")(response)");
                    
                },
                "expired-callback": function() {
                    
                    if (recaptcha.data("form-id") !== "")
                        jQuery("#" + recaptcha.data("input-id"), "#" + recaptcha.data("form-id")).val("");
                    else
                        jQuery("#" + recaptcha.data("input-id")).val("");
                    
                    if (recaptcha.data("expired-callback"))
                        eval("(" + recaptcha.data("expired-callback") + ")()");
                    
                },
                "error-callback": function() {
                    
                    if (recaptcha.data("form-id") !== "")
                        jQuery("#" + recaptcha.data("input-id"), "#" + recaptcha.data("form-id")).val("");
                    else
                        jQuery("#" + recaptcha.data("input-id")).val("");
                    
                    if (recaptcha.data("error-callback"))
                        eval(recaptcha.data("error-callback"));
                    
                },
                "isolated": {$this->isolated}
            });
            
            recaptcha.data("recaptcha-client-id", clientId);
            if (recaptcha.data("size") == 'invisible') {
                const form = jQuery("#" + recaptcha.data("form-id"));
                form.find('[type="submit"]').on("click", function(event) {
                    event.preventDefault();
                    if (grecaptcha.getResponse()) {
                        form.submit();
                    } else {
                        grecaptcha.reset();
                        grecaptcha.execute();
                    }
                });
            }
        }
    });
}
JS
        , $view::POS_END);

        if (Yii::$app->request->isAjax) {
            $view->registerJs(<<< JS
if (typeof grecaptcha !== "undefined") {
    reCaptchaInit();
}
JS
            , $view::POS_END);
        }

    }

    /**
     * Registers a hidden field for storing a verification token
     */
    protected function registerHiddenField()
    {
        $id = $this->getWidgetId();
        if ($this->hasModel())
            $name = Html::getInputName($this->model, $this->attribute);
        else
            $name = $this->name;

        $options['id'] = $id;
        $options = $this->options;
        echo Html::input('hidden', $name, null, $options);
    }

}