<?php

namespace wdmg\validators;

/**
 * Yii2 ReCaptcha
 *
 * @category        Validators
 * @version         1.0.2
 * @author          Alexsander Vyshnyvetskyy <alex.vyshnyvetskyy@gmail.com>
 * @link            https://github.com/wdmg/yii2-recaptcha
 * @copyright       Copyright (c) 2019 - 2020 W.D.M.Group, Ukraine
 * @license         https://opensource.org/licenses/MIT Massachusetts Institute of Technology (MIT) License
 *
 */

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\Validator;
use yii\httpclient\Client;

class ReCaptchaValidator extends Validator
{

    public $secretKey; // Your private secret key for verification
    public $verifyURL; // URL for reCaptcha token validation ('https://www.google.com/recaptcha/api/siteverify' or use alternative URL if necessary 'https://www.recaptcha.net/recaptcha/api/siteverify')
    public $checkHost = true; // Check the host when verify error`s
    public $skipOnEmpty = false;
    public $message = 'Please confirm that you are not a robot.';

    private $_httpClient;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();

        if (!$this->secretKey && isset(Yii::$app->params["recaptcha.secretKey"]))
            $this->secretKey = Yii::$app->params["recaptcha.secretKey"];

        if (!$this->verifyURL && isset(Yii::$app->params["recaptcha.verifyURL"]))
            $this->verifyURL = Yii::$app->params["recaptcha.verifyURL"];

        if (!$this->secretKey)
            throw new InvalidConfigException("Required validator param `secretKey` isn't set.");

        if (!$this->verifyURL)
            throw new InvalidConfigException("Required validator param `verifyURL` isn't set.");

        if (!$this->message)
            throw new InvalidConfigException("Required validator param `message` isn't set.");

        $this->_httpClient = new Client();

    }

    /**
     * Validates the transferred token
     *
     * @param mixed $value
     * @return array|null
     * @throws Exception
     */
    protected function validateValue($value) {

        $isValid = false;
        if (is_null($value) || empty($value)) {
            $isValid = false;
            Yii::debug('Transferred token in null or empty', __METHOD__);
        } else {
            $response = $this->verifyCaptcha($value);
            if (!isset($response['success']) && isset($response['hostname'])) {
                if ($this->checkHost && $response['hostname'] !== Yii::$app->request->hostName) {
                    throw new Exception('Invalid reCaptcha verify response.');
                }
            }
            if ($response['success'] === true) {
                $isValid = true;
            } else {
                if (isset($response['error-codes'])) {
                    Yii::debug($response['error-codes'], __METHOD__);
                }
            }
        }

        // Flip the value where `null` should mean no validation errors
        return $isValid ? null : [$this->message, []];
    }

    /**
     * Makes a request to the reCaptcha verification server
     *
     * @param $value
     * @return mixed
     * @throws Exception
     */
    protected function verifyCaptcha($value) {
        $response = $this->_httpClient
            ->createRequest()
            ->setMethod('GET')
            ->setUrl($this->verifyURL)
            ->setData(['secret' => $this->secretKey, 'response' => $value, 'remoteip' => Yii::$app->request->userIP])
            ->send();

        if (!$response->isOk)
            throw new Exception('Unable to connect the verify captcha server. Response code: ' . $response->statusCode);

        return $response->data;
    }

}