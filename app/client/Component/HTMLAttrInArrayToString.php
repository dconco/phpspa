<?php

namespace phpSPA\Component;

function HTMLAttrInArrayToString (array $HtmlAttr)
{
   $attr = "";
   foreach ($HtmlAttr as $AttrName => $AttrValue)
   {
      $attr .= "$AttrName=\"$AttrValue\" ";
   }
   return $attr;
}