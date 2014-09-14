<?php

namespace Oligriffiths\Component\Emails;

use Nooku\Library;
use Nooku\Library\ObjectConfig;

class DispatcherEmail extends Library\DispatcherAbstract
{
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
            'email' => 'default',
            'content' => null,
            'from_email' => $app->getCfg('mailfrom'),
            'from_name' => $app->getCfg('fromname'),
            'mailer' => $app->getCfg('mailer'),
            'sendmail' => $app->getCfg('sendmail'),
            'smtp_auth' => $app->getCfg('smtpauth'),
            'smtp_user' => $app->getCfg('smtpuser'),
            'smtp_pass' => $app->getCfg('smtppass'),
            'smtp_host' => $app->getCfg('smtphost'),
            'smtp_port' => $app->getCfg('smtpport'),
            'smtp_security' => $app->getCfg('smtpsecure'),
            'attachments' => null
        ));

        parent::_initialize($config);
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
        $context->request = clone $context->getRequest();
        $request_data = $context->getRequest()->getData();

        //Validate from email
        if(!$from_email = $request_data->get('from_email', 'email', $this->getConfig()->from_email)){
            throw new \InvalidArgumentException('No from email address supplied');
        }

        //Validate from email
        if(!$this->getObject('lib:filter.email')->validate($from_email)){
            throw new \InvalidArgumentException('From email is not a valid email address');
        }

        //Validate to email
        if(!$to_email = $request_data->recipient_email){
            throw new \InvalidArgumentException('No recipient email address supplied');
        }

        //Validate to email
        if(!$this->getObject('lib:filter.email')->validate($to_email)){
            throw new \InvalidArgumentException('Recipient email is not a valid email address');
        }

        //Validate subject
        if(!$subject = $request_data->subject){
            throw new \InvalidArgumentException('No subject supplied');
        }

        //Get to/from names
        $from_name = $request_data->get('from_name','string',$this->getConfig()->from_name) ?: $from_email;
        $to_name = $request_data->recipient_name ?: $to_email;

        //Store the template in the context for use outside this action
        $context->getRequest()->setFormat('text');
        $context->text = $this->compile($context);

        $context->getRequest()->setFormat('html');
        $context->html = $this->compile($context);

        // Create the Transport
        switch($this->getConfig()->mailer)
        {
            case 'smtp':
                $transport = \Swift_SmtpTransport::newInstance(
                    $this->getConfig()->smtp_host,
                    $this->getConfig()->smtp_port,
                    $this->getConfig()->smtp_security
                )
                ->setUsername($this->getConfig()->smtp_user)
                ->setPassword($this->getConfig()->smtp_pass);
                break;

            case 'sendmail':
                $transport = \Swift_SendmailTransport::newInstance($this->getConfig()->sendmail);
                break;

            case 'mail':
            default:
                $transport = \Swift_MailTransport::newInstance();
                break;
        }

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($transport);

        // Create a message
        $message = \Swift_Message::newInstance($subject)
            ->setFrom(array($from_email => $from_name))
            ->setTo(array($to_email => $to_name))
            ->setBody($context->text)
            ->addPart($context->html, 'text/html');

        // Add attachments
        if ($attachments = $this->getConfig()->get('attachments', 'raw')) {

            foreach ($attachments as $filename => $body) {

                if (!$body) continue;

                $attachment = \Swift_Attachment::newInstance($body);
                if (is_string($filename)) $attachment->setFilename($filename);

                $message->attach($attachment);
            }
        }

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
        $request_data = $context->getRequest()->getData();

        //Get the format
        $format = $context->getRequest()->getFormat();

        //Merge the request data so it's available to the view
        $context->param->append($request_data->toArray());

        //Construct email controller identifier
        $identifier = $this->getIdentifier()->toArray();
        $identifier['path'] = array('controller');
        $identifier['name'] = 'email';

        //Render the email view
        $context->param->email_content = $this->getConfig()->content ?: $this->getObject($identifier)->layout($this->getConfig()->email)->format($format)->render($context);

        //Render the template view
        $identifier['name'] = 'template';
        return $this->getObject($identifier)->layout($this->getConfig()->template)->format($format)->render($context);
    }
}