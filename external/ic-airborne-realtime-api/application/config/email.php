<?php
$default_from_email = getenv('DEFAULT_FROM_EMAIL');
$default_from_name = getenv('BRAND_NAME');

$templates = array(
    'inbound-email-file-upload-success' => array(
        'subject' => 'Inbound Email: File was successfully uploaded'
    ),
    'inbound-email-file-upload-internal-server-error' => array(
        'subject' => 'Inbound Email: Unexpected server error'
    ),
    'inbound-email-file-upload-wrong-email-error' => array(
        'subject' => 'Inbound Email: Wrong email address'
    ),
    'locale-email-template' => array(
        'subject' => 'Inbound Email: Locale email template'
    ),
    'sign-up-confirm-email' => array(
        'subject' => 'Please complete your ' . $default_from_name . ' signup'
    ),
    'sign-up-welcome' => array(
        'subject' => 'Welcome to ' . $default_from_name . '!'
    ),
    'sign-up-invitation' => array(
        'subject' => 'Invitation to join ' . $default_from_name
    ),
    'portal-invitation' => array(
        'subject' => 'Invitation to the *|PORTAL_NAME|* portal'
    ),
    'sign-up-invitation-welcome' => array(
        'subject' => 'Welcome to ' . $default_from_name . '!'
    ),
    'shared-folder-notification' => array(
        'subject' => 'The folder *|FOLDER_NAME|* was shared with you'
    ),
    'shared-file-notification' => array(
        'subject' => 'The file *|FILE_NAME|* was shared with you'
    ),
    'shared-files-notification' => array(
        'subject' => 'The files were shared with you'
    ),
    'password-reset' => array(
        'subject' => 'Your password was reset successfully'
    ),
    'send-event-notification' => array(
        'subject' => $default_from_name . ' notification report'
    )
);
return array(
    'email' => array(
        'adapter' => 'mandrill',
        'api_url' => 'https://mandrillapp.com/api/1.0',
        'api_key' => getenv('MANDRILL_API_KEY'),
        'inbound_email_host' => getenv('INBOUND_EMAIL_HOST'),
        'templates_path' => '/data/email/templates',
        'default_from_email' => $default_from_email,
        'default_from_name' => $default_from_name,
        'templates' => $templates,
        'locale_path' => '/data/locale',
        'email_template' => 'email.locale'
    )
);
