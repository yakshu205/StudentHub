<?php

$json_file = __DIR__ . "/students.json";
$csv_file  = __DIR__ . "/students.csv";


$view_mode = isset($_GET['view']) && $_GET['view'] === 'csv' ? 'csv' : 'json';


$json_records = [];
if (file_exists($json_file)) {
    $raw = file_get_contents($json_file);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $json_records = $decoded;
    }
}


$csv_headers = [];
$csv_records = [];
if (file_exists($csv_file)) {
    $fp = fopen($csv_file, "r");
    if ($fp) {
        $first = true;
        while (($row = fgetcsv($fp)) !== false) {
            if ($first) {
                $csv_headers = $row;
                $first = false;
            } else {
                $csv_records[] = $row;
            }
        }
        fclose($fp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stored Student Records - Practical 7</title>
    <link rel="stylesheet" href="register.css">
    <style>
        .view-switch {
            text-align: center;
            margin-bottom: 20px;
        }
        .view-switch a {
            padding: 8px 18px;
            border-radius: 6px;
            display: inline-block;
            margin: 0 5px;
            background: #fff;
            border: 2px solid #75b6e1;
            color: #0b3d91;
            font-weight: bold;
        }
        .view-switch a.active {
            background: #75b6e1;
            color: #fff !important;
        }
        .stats-badge {
            background: #0b3d91;
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-left: 10px;
        }
        .actions-bar {
            text-align: center;
            margin-top: 25px;
        }
    </style>
</head>
<body>

<header>
<center>
<br>
<br>
<img src="https://admission.charusat.ac.in/View%20Assets/MainPage/assets/charusat%20images/logo.png" alt="StudentHub Logo" width="120">

    <h1>StudentHub Portal</h1>
    <h3>Stored Student Records (Practical 7)</h3>

    <p>
        <a href="register.php">➕ Register New Student</a>
        &nbsp;|&nbsp;
        <a href="../Practical-2/login.html">Login Here</a>
    </p>
<hr>
</center>
</header>

<div class="records-container">
    <div class="records-title">
        Stored Records Overview
        <span class="stats-badge">Total: <?php echo count($json_records); ?> Students</span>
    </div>

    <!-- Toggle View Mode: JSON or CSV -->
    <div class="view-switch">
        <a href="view_records.php?view=json" class="<?php echo $view_mode === 'json' ? 'active' : ''; ?>">
            📂 Read from students.json (<?php echo count($json_records); ?>)
        </a>
        <a href="view_records.php?view=csv" class="<?php echo $view_mode === 'csv' ? 'active' : ''; ?>">
            📊 Read from students.csv (<?php echo count($csv_records); ?>)
        </a>
    </div>

    <?php if ($view_mode === 'json'): ?>
        <?php if (empty($json_records)): ?>
            <p style="text-align:center; padding: 30px; color:#666; font-size:16px;">
                No records found in <code>students.json</code>. Submit the <a href="register.php">Registration Form</a> to add records!
            </p>
        <?php else: ?>
            <table class="records-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>DOB</th>
                        <th>Gender</th>
                        <th>Course</th>
                        <th>Skills</th>
                        <th>Address</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($json_records as $s): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($s['id'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['mobile'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['dob'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($s['gender'] ?? ''); ?></td>
                            <td><strong><?php echo htmlspecialchars($s['course'] ?? ''); ?></strong></td>
                            <td>
                                <?php 
                                    if (!empty($s['skills']) && is_array($s['skills'])) {
                                        foreach ($s['skills'] as $sk) {
                                            echo '<span class="badge">' . htmlspecialchars($sk) . '</span>';
                                        }
                                    } else {
                                        echo '<span style="color:#888;">None</span>';
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($s['address'] ?? '-'); ?></td>
                            <td><small><?php echo htmlspecialchars($s['registered_at'] ?? ''); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php else: ?>
        <?php if (empty($csv_records)): ?>
            <p style="text-align:center; padding: 30px; color:#666; font-size:16px;">
                No records found in <code>students.csv</code>. Submit the <a href="register.php">Registration Form</a> to add records!
            </p>
        <?php else: ?>
            <table class="records-table">
                <thead>
                    <tr>
                        <?php foreach ($csv_headers as $header): ?>
                            <th><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($csv_records as $row): ?>
                        <tr>
                            <?php foreach ($row as $idx => $cell): ?>
                                <td>
                                    <?php if ($idx === 0): ?>
                                        <strong>#<?php echo htmlspecialchars($cell); ?></strong>
                                    <?php elseif ($idx === 7 && $cell !== 'None'): ?>
                                        <?php 
                                            $skills_list = explode(', ', $cell);
                                            foreach ($skills_list as $sk) {
                                                echo '<span class="badge">' . htmlspecialchars($sk) . '</span>';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($cell); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <div class="actions-bar">
        <a href="register.php" class="view-btn">➕ Add Another Registration</a>
        <a href="students.json" target="_blank" class="view-btn" style="background:#555;">📄 Raw JSON</a>
        <a href="students.csv" download class="view-btn" style="background:#28a745;">📥 Download CSV</a>
    </div>
</div>

<hr>

<center>
    <small> <p>&copy; 2026 StudentHub. All Rights Reserved.</p></small>
</center>

</body>
</html>
