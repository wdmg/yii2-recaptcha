[![Progress](https://img.shields.io/badge/required-Yii2_v2.0.13-blue.svg)](https://packagist.org/packages/yiisoft/yii2) 
[![Github all releases](https://img.shields.io/github/downloads/wdmg/yii2-recaptcha/total.svg)](https://GitHub.com/wdmg/yii2-recaptcha/releases/)
[![GitHub version](https://badge.fury.io/gh/wdmg/yii2-recaptcha.svg)](https://github.com/wdmg/yii2-recaptcha)
![Progress](https://img.shields.io/badge/progress-in_development-red.svg)
[![GitHub license](https://img.shields.io/github/license/wdmg/yii2-recaptcha.svg)](https://github.com/wdmg/yii2-recaptcha/blob/master/LICENSE)

# Yii2 ReCaptcha
Google ReCaptcha widget for Yii2

# Requirements 
* PHP 5.6 or higher
* Yii2 v.2.0.20 and newest
* jQuery

# Installation
To install the widget, run the following command in the console:

`$ composer require "wdmg/yii2-recaptcha"`

# Usage
Add in your view:

    <?php
    
    use wdmg\widgets\ReCaptcha;
    ...
    
    $form = ActiveForm::begin();
    ...
    echo $form->field($model, 'userVerify')->widget(\wdmg\widgets\ReCaptcha::class, [
        'language' => 'uk-UA',
        'siteKey' => '_your_site_key_',
        'callbacks' => [
            'onload' => 'console.log("ReCaptcha: onload")',
            'success' => 'console.log("ReCaptcha: success")',
            'expired' => 'console.log("ReCaptcha: expired")',
            'error' => 'console.log("ReCaptcha: error")'
        ],
        'widgetOptions' => [
            'class' => 'pull-right'
        ]
    ]);
    ...
    ActiveForm::end();
    
    ?>

And add in your model validation rules:

    <?php
    
    use wdmg\validators\ReCaptchaValidator;
    ...
    
    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            ...
            ['userVerify', ReCaptchaValidator::class, 'secretKey' => '_your_secret_key_', 'message' => 'Please confirm that you are not a robot.'],
            ...
        ];
    }
    
    ?>

# Widget options

| Name          | Type    | Default                   | Description            |
|:------------- | ------- |:------------------------- |:---------------------- |
| language      | string  | `en`        | See https://developers.google.com/recaptcha/docs/language. |
| siteKey       | string  | null        | Your public sitekey. |
| apiURL        | string  | `//www.google.com/recaptcha/api.js` | The URL for reCaptcha API (or use alternative URL if necessary '//www.recaptcha.net/recaptcha/api.js').|
| callbacks     | array   | []          | Javascript callback`s for reCaptcha events. |
| render        | string  | `explicit`  | Optional. Whether to render the widget explicitly. Defaults to onload, which will render the widget in the first g-recaptcha tag it finds..  |
| theme         | string  | `light`     | Optional. The color theme of the widget |
| type          | string  | null        | Optional. The type of CAPTCHA to serve. |
| badge         | string  | null        | Optional. Reposition the reCAPTCHA badge. 'inline' lets you position it with CSS.  |
| size          | string  | null        | Optional. The size of the widget. Use `invisible` value for create an invisible widget bound to a div and programmatically executed. |
| tabIndex      | integer | 0           | Optional. The tabindex of the widget and challenge. If other elements in your page use tabindex, it should be set to make user navigation easier. |
| isolated      | boolean | false       | Optional. For plugin owners to not interfere with existing reCAPTCHA installations on a page. If true, this reCAPTCHA instance will be part of a separate ID space. |
| options       | array   | []          | Default input options. |
| widgetOptions | array   | []          | Default widget options. |

> This widget supports Google reCaptcha v2 in a normal and invisible way. Support for v3 is planned.

# Status and version
* v.1.0.0 - First release. Added widget and validator.