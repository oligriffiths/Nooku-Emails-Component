#Nooku emails component

The idea behind this component is very simple, standardise sending templated emails through Nooku.

At present, all email sending is handled within each component manually. This offers no opportunity to brand the outgoing emails, or do any kind of data merging. This component handles both.

##1. Sending emails

First step is to instantiate the email dispatcher

```php
$dispatcher = $this->getObject('com://oligriffiths/emails.dispatcher.email', /* $config, optional*/);
```

`$config` is optional, some defaults are pulled from the application /config/bootstrapper.php. The defaults are:

	* template' 		=> 'default',				//the "wrapping" template to use
	* layout' 			=> 'default',				//the "layout" to use if loading an email layout from the filesystem
	* content' 			=> null,					//email content
	* content_txt	 	=> null,					//custom txt email content
	* content_html	 	=> null,					//custom html email content
	* from_email 		=> bootstrapper->mailfrom,
	* from_name	 		=> bootstrapper->fromname,
	* mailer 			=> bootstrapper->mailer,    //one of smtp/sendmail/mail
	* sendmail			=> bootstrapper->sendmail,  //sendmail location
	* smtp_auth 		=> bootstrapper->smtpauth,
	* smtp_user 		=> bootstrapper->smtpuser,
	* smtp_pass 		=> bootstrapper->smtppass,
	* smtp_host 		=> bootstrapper->smtphost,
	* smtp_port 		=> bootstrapper->smtpport,
	* smtp_security 	=> bootstrapper->smtpsecure

All can be overridden when instantiating the object and passed as the second parameter `$config` as an array.

Once instantiated, send an email is pretty straight forward:

```php
$dispatcher->send(array(
	'recipient_email' => 'recipient@email.com', 
	'subject' => 'My subject', 
	'content' => "This is my email"
	'layout' => 'my_email' 	//Either supply content OR layout, not both, point 2 see below
));
```

The absolute minimum params, are `recipeient_email` and `subject` and either `content` or `layout`, however there are other parameters you may wish to supply. The full list is below, most are self explainatory:

	* from_email
	* from_name
	* recipient_email
	* recipient_name
	* recipients 		//array of recipients, keys being email address, value being name
	* subject
	* content			//email content
	* content_txt		//custom txt email content
	* content_html		//custom html email content
	* layout			//an email layout to load from the file system, specifiy EITHER content OR layout

The return value from `send()` is a boolean that indicates if the email sent successfully.

##2. Body copy

Body copy for the email can be supplied in 2 ways, as either a layout template or as a string.

###Layout template

This is the recommended approach. You can create email layouts on the filesystem and reference these when sending the email. Email layout templates must be placed within your active site template as template overrides, e.g. `/applications/site/public/theme/themename/templates/emails/email/{name}.{format}.php`

* {name} - is the name of the layout, this is then passed as 'layout' property passed to the `send()` method or as an option when instantiating the dispatcher
* {format} - either txt or html, for text ot html emails

Support will be added in the future to load these templates from within another component so email templates can be bundled with a component.

###String template

Pass the email content as a string, either `content_html` / `content_txt` or just `content`. 

`content_html` and `content_txt` will define specific content for html and txt emails, `content` will use the same content for both, and will have `nl2br()` run on the html version.

##3. Mail merge

The component will automatically mail merge any keys passed to the `send()` along with the defaults listed above method within the email body.

The format for mail merging is `{{VARIABLE}}`, in uppercase, e.g. `{{FROM_EMAIL}}`.

##4. Transport mechanisms

This component uses SwiftMailer internally, and has 3 main supported transport mechanisms:

* smtp
* sendmail
* mail

##5. Anything else

Stick to the code, dig in an look at the code!

Pull request welcome

Twitter: @oligriffiths
