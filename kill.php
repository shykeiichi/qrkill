<?php

session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';

if(!isset($_SESSION['qr']['id']))
{
    echo json_encode(['error' => 'Din session har gått ut. Vänligen logga in igen.']);
    die();
}

$postData = json_decode(file_get_contents('php://input'), true);

if(!isset($postData['secret']))
{
    echo json_encode(['error' => 'Ingen kod angiven.']);
    die();
}

$secret = $postData['secret'];

$sql = 'SELECT alive FROM qr_players WHERE qr_users_id = ?';
$alive = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetchColumn();

if($alive != 1)
{
    echo json_encode(['error' => 'Du är tyvärr ute ur spelet.']);
    die();
}

$sql = '
SELECT 
    event.id,
    target.alive,
    user.name,
    (
        target.qr_users_id = (
            SELECT target 
            FROM qr_players AS hunter 
            WHERE hunter.qr_users_id = ? AND hunter.qr_events_id = event.id
        )
    ) AS correct_secret
FROM qr_players AS target
JOIN qr_events AS event
JOIN qr_users AS user 
	ON event.id = target.qr_events_id 
    	AND NOW() > event.start_date 
        AND NOW() < event.end_date
        AND target.qr_users_id = user.id
WHERE target.secret = ?
';
$info =  DB::prepare($sql)->execute([$_SESSION['qr']['id'], $secret])->fetch();

if(!$info || $info['correct_secret'] == 0)
{
    echo json_encode(['error' => 'Koden du angav var inte korrekt']);
    die();
}

if($info['alive'] == 0)
{
    echo json_encode(['error' => 'Denna person är ute ur spelet.']);
    die();
}

$sql = 'UPDATE qr_players SET alive = 0 WHERE secret = ?';
DB::prepare($sql)->execute([$secret]);

$sql = '
INSERT INTO qr_kills (target, killer, qr_events_id) 
VALUES ((SELECT qr_users_id FROM qr_players WHERE secret = ?), ?, ?)
';
DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id'], $info['id']]);

$sql = "
SELECT qr_users_id
FROM qr_players
WHERE target IS NULL AND qr_events_id = ?
ORDER BY created_date ASC LIMIT 1
";
$playerWithoutTarget = DB::prepare($sql)->execute([$info['id']])->fetchColumn();

if($playerWithoutTarget)
{
    $sql = '
    UPDATE qr_players as killer
    JOIN qr_players AS victim ON victim.secret = ?
    JOIN qr_players AS new_player ON new_player.qr_users_id = ?
    SET new_player.target = victim.target, killer.target = new_player.qr_users_id
    WHERE killer.qr_users_id = ? AND killer.qr_events_id = ?
    ';
    DB::prepare($sql)->execute([$secret, $playerWithoutTarget, $_SESSION['qr']['id'], $info['id']]);
}
else
{
    $sql = '
    UPDATE qr_players as killer
    JOIN (SELECT target FROM qr_players WHERE secret = ?) as victim
    SET killer.target = victim.target 
    WHERE qr_users_id = ? AND qr_events_id = ?
    ';
    DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id'], $info['id']]);
}

$sql = 'SELECT COUNT(*) FROM qr_players WHERE alive = 1 AND qr_events_id = ?';
$playersLeft = DB::prepare($sql)->execute([$info['id']])->fetchColumn();

$config = (array) json_decode(file_get_contents('priv/config.json'));    

if($config != false && isset($config['killfeed_webhook']) && $config['killfeed_webhook'] != '')
{
    if($playersLeft == 1)
    {
        $message = $_SESSION['qr']['name'] . " taggade " . $info['name'] . " och vann därmed QRTag! Grattis!";
    }
    else
    {
        $message = $_SESSION['qr']['name'] . " taggade " . $info['name'] . "!\nNu är det $playersLeft spelare kvar.";
    }
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query(array('content' => $message))
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($config['killfeed_webhook'], false, $context);
}

if($playersLeft == 1)
{
    echo json_encode(['success' => 'Du vann! Grattis!']);

}
else
{
    echo json_encode(['success' => 'Du taggade ditt mål! Du kommer nu tilldelas ett nytt. Lycka till!']);
}
