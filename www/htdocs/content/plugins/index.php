
<?php

function randonCharly()
{
    $names = array(

        'Como mata el viento norte
Cuando agosto está en el día
Y el espacio nuestros cuerpos ilumina',

        'Un mendigo muestra joyas
A los ciegos de la esquina
Y un cachorro del señor nos alucina',

        'Háblame solo
De nubes y sal
No quiero saber nada
Con la miseria del mundo hoy',

        'Hoy es un buen día
Hay algo en paz
La tierra es nuestra hermana',

        'Marte no cede
Al poder del sol
Venus nos enamora
La Luna sabe de su atracción',

        'Mientras nosotros
Morimos aquí
Con los ojos cerrados
No vemos más que nuestra nariz',

        'Como mata el viento norte
Cuando agosto está en el día
Y el espacio nuestros cuerpos ilumina',

        'Señor noche, se mi cuna
Señor noche, se mi día
Mi pequeña almita baila
De alegría, de alegría'

    );
    return $names[rand(0, count($names) - 1)];
}


echo '
<pre>' . randonCharly() . '</pre>';
