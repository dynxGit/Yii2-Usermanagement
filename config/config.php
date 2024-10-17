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
    /**
     * Format of pin (sections separated by "-")
     * Section type : last Char in section (C:Character N:Number)
     * section count: Number of the chars is the left part of the section 
     * 
     * if section count is not number the section is fix!  (XYZ-4N = XYZ-1234)
     * default: "3C-4N" result 3 character and 4 number : ABC-1234
     */
    'pinFormat' => "3C-4N",

    'tryout' => 30,
    /**
     * Extra config data available from module using "cfg" before arraykey
     * for example extraParameter as attribute  Module->cfgextraParameter
     */
    'config' => [

    ]

];
