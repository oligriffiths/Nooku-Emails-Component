<?php

namespace Nooku\Component\Emails;

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
        $config->append(array(
            'template' => 'default',
            'email' => 'default',
            'content' => null
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
        $request_data = $context->getRequest()->getData();

        //Validate from email
        if(!$from_email = $request_data->from_email){
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
        $from_name = $request_data->from_name ?: $from_email;
        $to_name = $request_data->recipient_name ?: $to_email;

        //Merge the request data so it's available to the view
        $context->param->append($request_data->toArray());

        //Construct email controller identifier
        $identifier = $this->getIdentifier()->toArray();
        $identifier['path'] = array('controller');
        $identifier['name'] = 'email';

        //Render the email view
        $context->param->email_content = $this->getObject($identifier)->layout($this->getConfig()->email)->render($context);

        //Render the template view
        $identifier['name'] = 'template';
        $template = $this->getObject($identifier)->layout($this->getConfig()->template)->render($context);

        // Create the Transport
        $transport = \Swift_MailTransport::newInstance();

        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($transport);

        // Create a message
        $message = \Swift_Message::newInstance($subject)
            ->setFrom(array($from_email => $from_name))
            ->setTo(array($to_email => $to_name))
            ->setBody($template);

        // Send the message
        return $mailer->send($message);
    }
}