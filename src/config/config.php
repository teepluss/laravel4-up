<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Attachment Model
    |--------------------------------------------------------------------------
    |
    | When using the "eloquent" driver, we need to know which
    | Eloquent models should be used throughout Up.
    |
    */

    'attachments' => array(

        'model' => '\Teepluss\Up\Attachments\Eloquent\Attachment'

    ),

    /*
    |--------------------------------------------------------------------------
    | Attachment Relate Model
    |--------------------------------------------------------------------------
    |
    | When using the "eloquent" driver, we need to know which
    | Eloquent models should be used throughout Up.
    |
    */

    'attachmentRelates' => array(

        'model' => '\Teepluss\Up\AttachmentRelates\Eloquent\AttachmentRelate'

    ),

    /*
    |--------------------------------------------------------------------------
    | Callback
    |--------------------------------------------------------------------------
    |
    | Placeholder for image not found.
    |
    */

    'placeholder' => function($attachmentId)
    {
        //return URL::asset('placeholder/notfound.png');
        return 'Image not found.';
    }

);