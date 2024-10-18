<?php
namespace dynx\models\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use dynx\Module;

class UserPinBehavior extends Behavior
{
    /**
     * Generates new pin following module pinFormat 
     */
    public function generatePin()
    {
        $pin = "";
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $numbers = "123456789";
        $mod = Module::getInstance();
        $format = $mod->pinFormat;
        foreach (explode("-", $format) as $idx => $f) {
            if ($idx > 0) $pin .= "-";
            $count = substr($f, 0, -1);
            $type = substr($f, -1);
            if (is_numeric($count)) {
                for ($i = 0; $i < $count; $i++) {
                    switch ($type) {
                        case "C":
                            $pin .=  substr($chars, rand(0, strlen($chars)-1), 1);
                            break;
                        case "N":
                            $pin .= rand(0, 9);
                            break;
                    }
                }
            } else $pin .= $f;
        }
        return $pin;
    }
    /**
     * Build HTML code to Show Pin in boxes (especially for e-mail)
     */

    public function GetPinHtmlCode(){
        $mod = Module::getInstance();
        $css=[
            'color_white'=>"#fff",
            'color_dark'=>"#1a8754",

            'div'=>"text-align:center;margin:20px;height:50px;",
            'box'=>"border-radius:5px;border:1px solid #ccc;width:50px;padding:10px 15px;font-weight:bold;font-size:24px;margin:0 5px;display:inline-block;",
      
        ];
        $text = "";
        $graybox=true;
        $pin = $this->owner->pin;
        for ($i = 0; $i < strlen($pin); $i++) {
            $value = substr($pin, $i, 1);
            if($value=="-"){
                $graybox=false;
            } else{
                $color=$graybox?"color:$css[color_white];border-color:$css[color_dark];background:$css[color_dark]":"border-color:$css[color_dark];color:$css[color_dark];background_$css[color_white]";
                $text .= "<span style='$css[box];$color'>" . $value . "</span>";
            }
        }
        return "<div style='$css[div]'>$text</div>";

    }

}