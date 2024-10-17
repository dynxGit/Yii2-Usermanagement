<?php
return [
    /**
     * Supersuer email
     * @todo Clear default value
     */
    "SUAemail" => "sua@dynx.hu",//TODO Clear default value
    /**
     * LOGIN input type (email or username default:email)
     */
    "loginInput" => "email",
    /**
     * Setting for mail options
     */
    'mailOptions' => [
        'viewPath' => '@dynx/mail',
        'htmlLayout' => '@dynx/mail/layouts/html',
        'textLayout' => '@dynx/mail/layouts/text',
    ],
    'tryout'=>30

];
