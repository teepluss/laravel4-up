<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | When using the "eloquent" driver, we need to know which
    | Eloquent models should be used throughout Up.
    |
    */

    'attachments' => array(

        'model' => 'Teepluss\Up\Attachments\Eloquent\Attachment',
    ),

    /*
    |--------------------------------------------------------------------------
    | Callback
    |--------------------------------------------------------------------------
    |
    | Image not found event.
    |
    */

    'failure' => function($attachmentId)
    {
        return 'Image not found.';
    }

);