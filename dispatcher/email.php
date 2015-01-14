<?php

namespace Oligriffiths\Component\Emails;

use Nooku\Library;

class DispatcherEmail extends Library\DispatcherAbstract
{
    /**
     * @var \Swift_Transport_SmtpAgent
     */
    protected $_transport;


    /**
     * Constructor.
     *
     * @param ObjectConfig $config	An optional ObjectConfig object with configuration options.
     */
    public function __construct(Library\ObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('before.send', '_prepareData');
        $this->addCommandCallback('before.send', '_validate');
    }


    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  ObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(ObjectConfig $config)
    {
        $app = $this->getObject('application');

        $config->append(array(
            'template' => 'default',
            'layout' => 'default',
            'content' => null,
            'from_email' => $app->getConfig()->mailfrom,
            'from_name' => $app->getConfig()->fromname,
            'mailer' => $app->getConfig()->mailer,
            'sendmail' => $app->getConfig()->sendmail,
            'smtp_auth' => $app->getConfig()->smtpauth,
            'smtp_user' => $app->getConfig()->smtpuser,
            'smtp_pass' => $app->getConfig()->smtppass,
            'smtp_host' => $app->getConfig()->smtphost,
            'smtp_port' => $app->getConfig()->smtpport,
            'smtp_security' => $app->getConfig()->smtpsecure
        ));

        parent::_initialize($config);
    }


    /**
     * Prepares the supplied data by merging with globals
     *
     * @param Library\ControllerContextInterface $context
     */
    protected function _prepareData(Library\ControllerContextInterface $context)
    {
        //Fetch request data
        $data = $context->param->append(array(
            'from_email' => $this->getConfig()->from_email,
            'from_name' => $this->getConfig()->from_name,
            'content' => $this->getConfig()->content,
            'layout' => $this->getConfig()->layout,
        ));

        //If no recipients defined and a single recipient
        if(!$data->recipients && $data->recipient_email){
            $data->recipients = array($data->recipient_email => $data->recipient_name ?: $data->recipient_email);
        }

        $context->param = $data;
    }


    /**
     * Validates the supplied data
     *
     * @param Library\ControllerContextInterface $context
     */
    protected function _validate(Library\ControllerContextInterface $context)
    {
        $data = $context->param;

        //Validate from email
        if(!$from_email = $data->get('from_email', $this->getConfig()->from_email)){
            throw new \InvalidArgumentException('No from email address supplied');
        }

        //Validate from email
        if(!$this->getObject('lib:filter.email')->validate($from_email)){
            throw new \InvalidArgumentException('From email is not a valid email address');
        }

        //Check recipients is an array
        if(!$data->recipients){
            throw new \InvalidArgumentException('Recipients must be be provided as an array');
        }

        //Ensure at least 1 recipient exists
        $recipients = $data->recipients->toArray();
        if(empty($recipients)){
            throw new \InvalidArgumentException('At least 1 recipient must be supplied');
        }

        //Set default recipient email
        $data->recipient_email = key($recipients);
        $data->recipient_name = current($recipients);

        //Validate recipients
        foreach($data->recipients AS $email => $name){

            //Validate to email
            if(!$this->getObject('lib:filter.email')->validate($email)){
                throw new \InvalidArgumentException('Recipient email is not a valid email address');
            }
        }

        //Validate subject
        if(!$subject = $data->subject){
            throw new \InvalidArgumentException('No subject supplied');
        }
    }


    /**
     * Sends email via SwiftMailer merging in supplied data with the templates
     *
     * @param Library\ControllerContextInterface $context
     * @return int|void
     * @throws \InvalidArgumentException
     */
    protected function _actionSend(Library\ControllerContextInterface $context)
    {
        //Fetch request data
        $data = $context->param;

        //Render the text template
        $context->getRequest()->setFormat('txt');
        $context->text = $this->compile($context);

        //Render the html template
        $context->getRequest()->setFormat('html');
        $context->html = $this->compile($context);

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($this->getTransport());

        // Create a message
        $message = \Swift_Message::newInstance($data->subject)
            ->setFrom(array($data->from_email => $data->from_name))
            ->setTo($data->recipients->toArray())
            ->setBody($context->text)
            ->addPart($context->html, 'text/html');

        // Add attachments
        if ($attachments = $data->attachments) {

            foreach ($attachments->toArray() AS $filename => $body) {

                if (!$body) continue;

                $attachment = \Swift_Attachment::newInstance($body);
                if (is_string($filename)) $attachment->setFilename($filename);

                $message->attach($attachment);
            }
        }

        //Ensure message is available later
        $context->message = $message;

        // Send the message
        return $mailer->send($message);
    }


    /**
     * Compiles the email & merges in merge data
     *
     * @param Library\ControllerContextInterface $context
     * @return mixed
     */
    protected function _actionCompile(Library\ControllerContextInterface $context)
    {
        //Fetch request data
        $data = $context->param;

        //Get the format
        $format = strtolower($context->getRequest()->getFormat());

        //Get the email layout
        $layout = $data->layout;

        //Construct email controller identifier
        $identifier = $this->getIdentifier()->toArray();
        $identifier['path'] = array('controller');
        $identifier['name'] = 'email';

        //Get format specific content, fallback to non-format specific and render blank layout
        $content_format = $data->get('content_'.$format);
        $content = $data->get('content') ?: $this->getConfig()->content;
        if($content_format || $content){
            $data->content = $content_format ?: ($format == 'html' ? nl2br($content) : $content);
            $layout = 'blank';
        }

        //Render the email template
        $context->param->email_content = $data->content ?: $this->getObject($identifier)->layout($layout)->format($format)->render($data);

        //Render the template view
        $identifier['name'] = 'template';
        return $this->getObject($identifier)->layout($this->getConfig()->template)->format($format)->render($data);
    }


    /**
     * Gets the current set transport according to the config
     *
     * @return \Swift_Transport_SmtpAgent
     */
    protected function getTransport()
    {
        if(!$this->_transport) {

            switch ($this->getConfig()->mailer) {
                case 'smtp':
                    $this->_transport = \Swift_SmtpTransport::newInstance(
                        $this->getConfig()->smtp_host,
                        $this->getConfig()->smtp_port,
                        $this->getConfig()->smtp_security
                    )
                        ->setUsername($this->getConfig()->smtp_user)
                        ->setPassword($this->getConfig()->smtp_pass);
                    break;

                case 'sendmail':
                    $this->_transport = \Swift_SendmailTransport::newInstance($this->getConfig()->sendmail);
                    break;

                case 'mail':
                default:
                    $this->_transport = \Swift_MailTransport::newInstance();
                    break;
            }
        }

        return $this->_transport;
    }
}