<?php
require_once __DIR__ . '/../../config.php';

define('PLATFORM_BASE', base_url . 'platform/');

function platform_redirect($path = '')
{
    header('Location: ' . PLATFORM_BASE . ltrim($path, '/'));
    exit;
}

function platform_require_auth()
{
    if (!platform_logged_in()) {
        platform_redirect('login.php');
    }
}

function platform_conn()
{
    global $conn;
    return $conn;
}

function platform_layout($title, $contentFile, $data = array())
{
    global $conn;
    extract($data);
    $pageTitle = $title;
    include __DIR__ . '/layout.php';
}

/** Subscription end dates for platform UI (status-aware). */
function platform_subscription_display(array $sub)
{
    $status = strtolower(trim((string)($sub['status'] ?? '')));
    if ($status === 'trial') {
        $trialEnd = $sub['trial_ends_at'] ?? '';
        return array(
            'end_label' => 'Trial Ends',
            'end_value' => ($trialEnd !== '' && $trialEnd !== null) ? $trialEnd : '—',
            'trial_end' => ($trialEnd !== '' && $trialEnd !== null) ? $trialEnd : '—',
            'period_end' => '—',
        );
    }
    $periodEnd = $sub['current_period_end'] ?? '';
    return array(
        'end_label' => 'Period End',
        'end_value' => ($periodEnd !== '' && $periodEnd !== null) ? $periodEnd : '—',
        'trial_end' => '—',
        'period_end' => ($periodEnd !== '' && $periodEnd !== null) ? $periodEnd : '—',
    );
}
