<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500); header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'msg' => 'Fatal Error: ' . $error['message']]); exit();
    }
});

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') exit(json_encode(['status'=>'error']));

$conn = new mysqli("sql100.infinityfree.com", "if0_39812412", "Bpiapp0101", "if0_39812412_bpi_stock");
if ($conn->connect_error) exit(json_encode(['status'=>'error', 'msg'=>'DB Fail']));

$action = $_POST['action'] ?? '';
$date   = $_POST['date'] ?? date('Y-m-d');

// --- SHIFT CONFIG ---
function getShiftConfig($conn, $date) {
    $lunchStartStr = "13:00"; $lunchMins = 60;
    
    $check = $conn->query("SHOW TABLES LIKE 'attendance_settings'");
    if($check && $check->num_rows > 0) {
        $q = $conn->query("SELECT lunch_minutes, lunch_start FROM attendance_settings WHERE id=1");
        if($q && $q->num_rows > 0) {
            $set = $q->fetch_assoc();
            $lunchMins = (int)$set['lunch_minutes'];
            if(!empty($set['lunch_start'])) $lunchStartStr = date("H:i", strtotime($set['lunch_start']));
        }
    }
    
    $factoryOpen = strtotime("$date 08:00:00");
    $factoryClose= strtotime("$date 20:00:00");
    $lunchStart  = strtotime("$date $lunchStartStr:00");
    $lunchEnd    = $lunchStart + ($lunchMins * 60);

    $b1_max = max(0, ($lunchStart - $factoryOpen) / 60);
    $b2_end = $lunchEnd + (180 * 60);
    $b2_max = 180;
    $b3_max = max(0, ($factoryClose - $b2_end) / 60);

    return [
        'b1_max' => $b1_max, 'b2_max' => $b2_max, 'b3_max' => $b3_max,
        'l_start'=> $lunchStartStr, 'l_dur' => $lunchMins,
        'lbl_b1' => "08:00 AM - " . date("g:i A", $lunchStart),
        'lbl_b2' => date("g:i A", $lunchEnd) . " - " . date("g:i A", $b2_end),
        'lbl_b3' => date("g:i A", $b2_end) . " - 08:00 PM"
    ];
}

try {
    // --- FETCH ---
    if ($action === 'fetch') {
        $cfg = getShiftConfig($conn, $date);
        
        // CHECK HOLIDAY STATUS
        $isHoliday = false;
        $hRes = $conn->query("SELECT * FROM holidays WHERE h_date='$date'");
        if($hRes && $hRes->num_rows > 0) $isHoliday = true;

        $att = [];
        $res = $conn->query("SELECT * FROM attendance WHERE att_date='$date'");
        if($res) while($r = $res->fetch_assoc()) $att[$r['employee_id']] = $r;

        $data = [];
        $users = $conn->query("SELECT id, full_name FROM users WHERE status='active' ORDER BY full_name");
        if($users) while($u = $users->fetch_assoc()) {
            $uid = $u['id'];
            $a = $att[$uid] ?? [];
            $data[] = [
                'id' => $uid, 'name' => $u['full_name'],
                'b1_p' => (int)($a['b1_present']??0), 'b1_d' => (int)($a['b1_deduct']??0),
                'b2_p' => (int)($a['b2_present']??0), 'b2_d' => (int)($a['b2_deduct']??0),
                'b3_p' => (int)($a['b3_present']??0), 'b3_d' => (int)($a['b3_deduct']??0),
                'early'=> (int)($a['early_mins']??0), 
                'late' => (int)($a['late_mins']??0),
                'total'=> round(($a['total_minutes']??0)/60, 2)
            ];
        }
        
        // Add 'is_holiday' to the meta response
        $cfg['is_holiday'] = $isHoliday;
        
        echo json_encode(['status'=>'success', 'data'=>$data, 'meta'=>$cfg]);
        exit();
    }

    // --- TOGGLE HOLIDAY ---
    if ($action === 'toggle_holiday') {
        $check = $conn->query("SELECT * FROM holidays WHERE h_date='$date'");
        
        if($check && $check->num_rows > 0) {
            // REMOVE HOLIDAY (Enable Attendance)
            $conn->query("DELETE FROM holidays WHERE h_date='$date'");
            $status = false;
        } else {
            // SET HOLIDAY (Disable Attendance)
            // 1. Insert holiday record
            $conn->query("INSERT INTO holidays (h_date) VALUES ('$date')");
            
            // 2. IMPORTANT: Wipe all attendance data for this date
            $conn->query("DELETE FROM attendance WHERE att_date='$date'");
            
            $status = true;
        }
        echo json_encode(['status'=>'success', 'is_holiday'=>$status]);
        exit();
    }

    // --- UPDATE ANY FIELD (Single Handler) ---
    if ($action === 'update_field') {
        $eid = (int)$_POST['eid'];
        $type = $_POST['type']; // 'shift' or 'ot'
        $cfg = getShiftConfig($conn, $date);

        // Get current
        $ex = $conn->query("SELECT * FROM attendance WHERE employee_id=$eid AND att_date='$date'")->fetch_assoc();
        $c = $ex ? $ex : ['b1_present'=>0,'b1_deduct'=>0,'b2_present'=>0,'b2_deduct'=>0,'b3_present'=>0,'b3_deduct'=>0, 'early_mins'=>0, 'late_mins'=>0];

        // Apply Change
        if($type === 'shift') {
            $block = $_POST['block'];
            $c[$block.'_present'] = (int)$_POST['state'];
            $c[$block.'_deduct']  = (int)$_POST['deduct'];
        } elseif($type === 'ot') {
            $ot_type = $_POST['ot_type']; // 'early' or 'late'
            $c[$ot_type.'_mins'] = (int)$_POST['mins'];
        }

        // Recalculate Total
        $t1 = $c['b1_present'] ? ($cfg['b1_max'] - $c['b1_deduct']) : 0;
        $t2 = $c['b2_present'] ? ($cfg['b2_max'] - $c['b2_deduct']) : 0;
        $t3 = $c['b3_present'] ? ($cfg['b3_max'] - $c['b3_deduct']) : 0;
        
        $total = max(0, $t1 + $t2 + $t3 + $c['early_mins'] + $c['late_mins']);

        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, att_date, b1_present, b1_deduct, b2_present, b2_deduct, b3_present, b3_deduct, early_mins, late_mins, total_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE b1_present=VALUES(b1_present), b1_deduct=VALUES(b1_deduct), b2_present=VALUES(b2_present), b2_deduct=VALUES(b2_deduct), b3_present=VALUES(b3_present), b3_deduct=VALUES(b3_deduct), early_mins=VALUES(early_mins), late_mins=VALUES(late_mins), total_minutes=VALUES(total_minutes)");
        
        $stmt->bind_param("isiiiiiiiid", $eid, $date, $c['b1_present'], $c['b1_deduct'], $c['b2_present'], $c['b2_deduct'], $c['b3_present'], $c['b3_deduct'], $c['early_mins'], $c['late_mins'], $total);
        $stmt->execute();
        echo json_encode(['status'=>'success']);
        exit();
    }

    // --- MARK ALL ---
    if ($action === 'mark_all') {
        $cfg = getShiftConfig($conn, $date);
        $full = $cfg['b1_max'] + $cfg['b2_max'] + $cfg['b3_max'];
        $conn->query("INSERT INTO attendance (employee_id, att_date, b1_present, b2_present, b3_present, total_minutes) SELECT id, '$date', 1, 1, 1, $full FROM users WHERE status='active' ON DUPLICATE KEY UPDATE b1_present=1, b1_deduct=0, b2_present=1, b2_deduct=0, b3_present=1, b3_deduct=0, total_minutes = $full + early_mins + late_mins");
        echo json_encode(['status'=>'success']);
        exit();
    }

    // --- LUNCH SETTINGS ---
    if ($action === 'update_settings') {
        $start = $_POST['start_time']; $dur = (int)$_POST['duration'];
        $conn->query("INSERT INTO attendance_settings (id, lunch_start, lunch_minutes) VALUES (1, '$start', $dur) ON DUPLICATE KEY UPDATE lunch_start='$start', lunch_minutes=$dur");
        echo json_encode(['status'=>'success']);
        exit();
    }

} catch(Exception $e) { echo json_encode(['status'=>'error','msg'=>$e->getMessage()]); }
?>