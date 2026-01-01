<?php
define("level","../");
require_once(level."inc/standard_includes.php");

if (!isset($_GET['exc_id'])) {
    die("Keine Übungs-ID übergeben!");
}

$exc_id = intval($_GET['exc_id']);
$exc_data = $db->sql_query_with_fetch(
    "SELECT * FROM reaction_exercises WHERE re_id=:exc_id",
    ['exc_id' => $exc_id]
);

try {

if (!IS_AJAX) {

    $myPage = new page();
    $myHTML = new html();
    $myPage->set_title($exc_data->re_title);
    if (!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

    $myPage->add_css_link('inc/css/overview.css');
    $myPage->add_content("<h1>{$myPage->get_title()}</h1>");
    $myPage->add_content("<a href='index.php'><button class='orange'><<</button></a>");
    $myPage->add_content("<a href='details.php?exc_id=".$exc_id."'><button>Übung starten</button></a>");

    /* ===============================
       Muster laden
    =============================== */
    $pattern = [];
    $db->sql_query("
        SELECT rep_id
        FROM reaction_exercises_positions
        WHERE rep_re_id = ".$exc_id."
        ORDER BY rep_id
    ");
    while ($p = $db->get_next_res()) {
        $pattern[] = (int)$p->rep_id;
    }

    if (!$pattern) {
        $myPage->add_content("<p><em>Kein Muster definiert.</em></p>");
        print $myPage->get_html_code();
        exit;
    }
    $patternLength = count($pattern);

    /* ===============================
       Live-Daten (MIT Session-ID)
    =============================== */
    $db->sql_query("
        SELECT
            repl_duration,
            rep_id,
            repl_ts,
            repl_session_id,
            user_account,
            user_id
        FROM reaction_exercises_positions_live
        LEFT JOIN reaction_exercises_positions ON repl_pos_id = rep_id
        LEFT JOIN users ON repl_user_id = user_id
        WHERE rep_re_id = ".$exc_id."
        ORDER BY repl_session_id, repl_ts
    ");

    $users = [];

    $currentSession = null;
    $lastSessionId  = null;

    $patternIndex = 0;
    $currentRunTime = 0;
    $expectedRuns = (int)$exc_data->re_repetitions;

    while ($d = $db->get_next_res()) {

        $userId     = (int)$d->user_id;
        $userName   = $d->user_account;
        $sessionId  = $d->repl_session_id;
        $time       = strtotime($d->repl_ts);
        $duration   = (float)$d->repl_duration;
        $repId      = (int)$d->rep_id;

        /* ===============================
           Neue Session nur bei neuer Session-ID
        =============================== */
        $newSession = $currentSession === null || $sessionId !== $lastSessionId;

        if ($newSession) {

            if ($currentSession !== null) {
                if (!isset($users[$currentSession['user_id']])) {
                    $users[$currentSession['user_id']] = [
                        'user' => $currentSession['user_name'],
                        'sessions' => []
                    ];
                }

                $users[$currentSession['user_id']]['sessions'][] = [
                    'runs' => $currentSession['runs'],
                    'total_duration' => $currentSession['total_duration'],
                    'fastest_run' => $currentSession['fastest_run'],
                    'slowest_run' => $currentSession['slowest_run'],
                    'complete' => $currentSession['runs'] == $expectedRuns,
                    'start_time' => $currentSession['start_time']
                ];
            }

            // neue Session initialisieren
            $currentSession = [
                'user_id' => $userId,
                'user_name' => $userName,
                'runs' => 0,
                'total_duration' => 0,
                'fastest_run' => null,
                'slowest_run' => null,
                'start_time' => $time
            ];

            $patternIndex = 0;
            $currentRunTime = 0;
        }

        /* ===============================
           Dauer aufsummieren
        =============================== */
        $currentSession['total_duration'] += $duration;

        /* ===============================
           1:1 Musterprüfung
        =============================== */
        if ($repId === $pattern[$patternIndex]) {

            $currentRunTime += $duration;
            $patternIndex++;

            if ($patternIndex === $patternLength) {

                $currentSession['runs']++;

                $currentSession['fastest_run'] =
                    is_null($currentSession['fastest_run'])
                    ? $currentRunTime
                    : min($currentSession['fastest_run'], $currentRunTime);

                $currentSession['slowest_run'] =
                    is_null($currentSession['slowest_run'])
                    ? $currentRunTime
                    : max($currentSession['slowest_run'], $currentRunTime);

                $patternIndex = 0;
                $currentRunTime = 0;
            }

        } else {
            $patternIndex = 0;
            $currentRunTime = 0;
        }

        $lastSessionId = $sessionId;
    }

    /* ===============================
       Letzte Session speichern
    =============================== */
    if ($currentSession !== null) {
        if (!isset($users[$currentSession['user_id']])) {
            $users[$currentSession['user_id']] = [
                'user' => $currentSession['user_name'],
                'sessions' => []
            ];
        }

        $users[$currentSession['user_id']]['sessions'][] = [
            'runs' => $currentSession['runs'],
            'total_duration' => $currentSession['total_duration'],
            'fastest_run' => $currentSession['fastest_run'],
            'slowest_run' => $currentSession['slowest_run'],
            'complete' => $currentSession['runs'] == $expectedRuns,
            'start_time' => $currentSession['start_time']
        ];
    }

    /* ===============================
       Ranking: beste vollständige Session
    =============================== */
    uasort($users, function($a,$b){
        $bestA = min(array_column(array_filter($a['sessions'], fn($s)=>($s['complete']??false)), 'total_duration') ?: [PHP_FLOAT_MAX]);
        $bestB = min(array_column(array_filter($b['sessions'], fn($s)=>($s['complete']??false)), 'total_duration') ?: [PHP_FLOAT_MAX]);
        return $bestA <=> $bestB;
    });

    /* ===============================
       HTML-Ausgabe (unverändert)
    =============================== */
    $myPage->add_content("<div class='leaderboard'>");
    $rank = 1;

    foreach($users as $user) {

        $myPage->add_content("
        <div class='card'>
            <div class='card-left'>
                <div class='rank'>#{$rank}</div>
                <div class='user-name'>".htmlspecialchars($user['user'])."</div>
                <div class='num'>".count($user['sessions'])." Session(s)</div>
            </div>
            <div class='card-right'>
        ");

        $maxTotal = max(array_column($user['sessions'],'total_duration'));

        foreach($user['sessions'] as $sess) {

            $class = ($sess['complete'] ?? false) ? '' : 'incomplete';
            $label = ($sess['complete'] ?? false) ? '' : '(unvollständig)';
            $sessionLabel = date("d.m.Y H:i", $sess['start_time']);

            $totalPercent = $maxTotal > 0 ? ($sess['total_duration'] / $maxTotal) * 100 : 0;
            $fastPercent  = $sess['fastest_run'] ? ($sess['fastest_run'] / $maxTotal) * 100 : 0;
            $slowPercent  = $sess['slowest_run'] ? ($sess['slowest_run'] / $maxTotal) * 100 : 0;

            $myPage->add_content("
            <div class='session $class'>
                <div class='bars'>
                    <div class='bar-row'><span class='time'><strong>".number_format($sess['total_duration'],2)."s</strong></span><div class='bar total' style='width:{$totalPercent}%;'></div></div>
                    <div class='bar-row'><span class='time'><strong>".number_format($sess['fastest_run'],2)."s</strong></span><div class='bar fastest' style='width:{$fastPercent}%;'></div></div>
                    <div class='bar-row'><span class='time'><strong>".number_format($sess['slowest_run'],2)."s</strong></span><div class='bar slowest' style='width:{$slowPercent}%;'></div></div>
                </div>
                <div class='session-info'>{$sessionLabel} | {$sess['runs']} Runs {$label}</div>
            </div>
            ");
        }

        $myPage->add_content("</div></div>");
        $rank++;
    }

    $myPage->add_content("</div>");
    print $myPage->get_html_code();
}

} catch(Exception $e){
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
}
?>
