<?php

function HomePage (): string
{
   $name = 'dconco';

   return <<<HTML
      <div>
         <p>Welcome to my PHP SPA project! @$name</p>
         <Link to="/login" text="GO TO LOGIN" />
      </div>
   HTML;
}