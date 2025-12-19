<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feedback Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for feedback system including admin emails that can
    | change feedback status and edit/delete feedback items. to add multiple admin emails, separate them with commas.
    |
    */

    'admin_emails' => env('FEEDBACK_ADMIN_EMAILS', ''),
    'feedbacks_per_page' => env('FEEDBACKS_PER_PAGE', 30),
];
