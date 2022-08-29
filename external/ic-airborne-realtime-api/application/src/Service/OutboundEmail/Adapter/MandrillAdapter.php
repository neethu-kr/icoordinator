<?php

namespace iCoordinator\Service\OutboundEmail\Adapter;

class MandrillAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \Mandrill
     */
    private $mandrill;

    /**
     * @var array
     */
    private $to = array();

    /**
     * @var string
     */
    private $fromEmail = null;

    /**
     * @var string
     */
    private $fromName = null;

    /**
     * @var string
     */
    private $subject = null;

    /**
     * @var string
     */
    private $emailLang = null;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $email
     * @param null $name
     */
    public function addTo($email, $name = null)
    {
        array_push($this->to, array('email' => $email, 'name' => $name));
    }

    /**
     * @param $email
     * @param null $name
     */
    public function setTo($email, $name = null)
    {
        $this->to = array(
            array('email' => $email, 'name' => $name)
        );
    }

    /**
     * @param $lang
     */
    public function setLang($emailLang)
    {
        $this->emailLang = $emailLang;
    }

    /**
     * @param $email
     * @param null $name
     */
    public function setFrom($email, $name = null)
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    /**
     * @param $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @param $templateName
     * @param array $vars
     * @return bool
     * @throws \Exception
     * @throws \Mandrill_Error
     */
    public function send($templateName, array $vars = array())
    {
        global $langArray;
        global $email_locale;
        $config = $this->config;

        /*if (!isset($config['templates'][$templateName])) {
            throw new \Exception('Email template with name "' . $templateName . '" does not exist');
        }*/

        include $config['locale_path'] . DIRECTORY_SEPARATOR . 'lang.'.($this->emailLang!=''?$this->emailLang:'en');
        include $config['locale_path'] . DIRECTORY_SEPARATOR . $config['email_template'];

        $templateContent = array();
        $globalMergeVars = array();

        $emailHeader = $email_locale[$templateName. '-header'];
        $emailBody = $email_locale[$templateName. '-body'];
        foreach ($vars as $key => $value) {
            $emailHeader = str_replace("*|".strtoupper($key)."|*", $value, $emailHeader);
            $emailBody = str_replace("*|".strtoupper($key)."|*", $value, $emailBody);
            array_push($globalMergeVars, array(
                'name' => strtoupper($key),
                'content' => $value
            ));
        }
        array_push($globalMergeVars, array(
            'name' => strtoupper('email_header'),
            'content' => $emailHeader
        ));
        array_push($globalMergeVars, array(
            'name' => strtoupper('email_body'),
            'content' => $emailBody
        ));
        if (empty($this->to)) {
            throw new \Exception('Destination email addresses are not set. Use addTo() method before sending');
        }

        if (empty($this->subject)) {
            /*if (isset($config['templates'][$templateName]['subject'])) {
                $this->subject = $config['templates'][$templateName]['subject'];
            } else {
                throw new \Exception('Subject is not set. Use setSubject() method before sending');
            }*/
            if (isset($email_locale[$templateName. '-subject'])) {
                $this->subject = $email_locale[$templateName. '-subject'];
            } else {
                throw new \Exception('Subject is not set. Use setSubject() method before sending');
            }
        }

        if (empty($this->fromEmail)) {
            if (isset($config['default_from_email'])) {
                $this->fromEmail = $config['default_from_email'];
            } else {
                throw new \Exception('From email is not set. Use setFrom() method before sending');
            }
        }

        if (empty($this->fromName)) {
            if (isset($config['default_from_name'])) {
                $this->fromName = $config['default_from_name'];
            }
        }

        $message = array(
            'to' => $this->to,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'subject' => $this->subject,
            'global_merge_vars' => $globalMergeVars
        );

        $async = false;
        $ip_pool = null;
        $send_at = null;

        try {
            $mandrill = $this->getMandrill();

            $results = $mandrill->messages->sendTemplate(
                //$templateName,
                'locale-email-template',
                $templateContent,
                $message,
                $async,
                $ip_pool,
                $send_at
            );

            foreach ($results as $result) {
                if ($result['status'] == 'invalid') {
                    //TODO: analyse result and log errors
                    throw new \Mandrill_Error();
                }
            }
        } catch (\Mandrill_Error $e) {
            // A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
            throw $e;
        }

        return true;
    }

    /**
     * @return \Mandrill
     */
    private function getMandrill()
    {
        $config = $this->config;
        if (!$this->mandrill) {
            $this->mandrill = new \Mandrill($config['api_key']);
        }

        return $this->mandrill;
    }

    /**
     * Used to create or update M
     */
    public function setupTemplates()
    {
        $config = $this->config;
        $mandrill = $this->getMandrill();

        $remoteTemplates = $mandrill->templates->getList();
        $remoteUpdateIDs = array();
        $remoteDeleteIDs = array();
        foreach ($remoteTemplates as $remoteTemplate) {
            if (array_key_exists($remoteTemplate['slug'], $config['templates'])) {
                array_push($remoteUpdateIDs, $remoteTemplate['slug']);
            } else {
                array_push($remoteDeleteIDs, $remoteTemplate['slug']);
            }
        }

        foreach ($remoteDeleteIDs as $templateId) {
            $mandrill->templates->delete($templateId);
        }

        foreach ($config['templates'] as $templateName => $templateConfig) {
            $code = file_get_contents($this->getTemplatePath($templateName, 'html'));
            $text = file_get_contents($this->getTemplatePath($templateName, 'txt'));

            if (in_array($templateName, $remoteUpdateIDs)) { //check if needs updates
                $remoteTemplate = $mandrill->templates->info($templateName);
                if (md5($remoteTemplate['code']) != md5($code) || md5($remoteTemplate['text'] != md5($text))) {
                    $mandrill->templates->update($templateName, null, null, null, $code, $text, true);
                }
            } else {
                $mandrill->templates->add($templateName, null, null, null, $code, $text, true);
            }
        }
    }

    /**
     * Returns full path of required template
     *
     * @param string $templateName
     * @param string $type one of 'html' or 'txt'
     * @return string
     */
    private function getTemplatePath($templateName, $type)
    {
        return $this->config['templates_path'] . '/' . $type . '/' . $templateName . '.' . $type;
    }
}
