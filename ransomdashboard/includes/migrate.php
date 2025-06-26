<?php
// Step 1: Include the database connection file 
require_once 'db.php';

// Include CSS file for styling output
echo "<link rel='stylesheet' href='../assets/css/migrate.css'>";

// Step 2: Define function that dynamically generates go back link
function getGoBackLink() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return $protocol . $host . str_replace('/includes', '/public', $uri) . '/dashboard.php';
}

// Step 3: List of MySQL migrations
$migrations = [
    '001_create_users_table' => "
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ",

    '002_add_profile_pic_to_users' => "
        ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255)
    ",

    '003_create_machine_keys_tables' => "
        CREATE TABLE IF NOT EXISTS machine_keys (
            key_id INT AUTO_INCREMENT PRIMARY KEY,
            machine_id VARCHAR(255) NOT NULL,
            encryption_key TEXT NOT NULL,
            receive_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            stop_signal TINYINT(1) DEFAULT 0
        )
    ",

    '004_add_status_to_machine_keys' => "
        ALTER TABLE machine_keys ADD COLUMN IF NOT EXISTS status VARCHAR(255) DEFAULT 'pending'
    "
];

// Step 4: Create the applied migrations table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS applied_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255),
    date_applied DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Step 5: Get the list of already applied migrations
$statement = $pdo->query("SELECT migration_name FROM applied_migrations");
$appliedMigrations = $statement->fetchAll(PDO::FETCH_COLUMN);

// Step 6: Apply pending migrations
foreach ($migrations as $migrationName => $sql) {
    if (!in_array($migrationName, $appliedMigrations)) {
        try {
            $pdo->exec($sql);
            $statement = $pdo->prepare("INSERT INTO applied_migrations (migration_name) VALUES (?)");
            $statement->execute([$migrationName]);
            echo "<div class='message success'>Successfully applied migration: $migrationName</div>";
        } catch (PDOException $e) {
            echo "<div class='message error'>Failed to apply migration $migrationName: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='message info'>Migration $migrationName has already been applied. Skipping.</div>";
    }
}

// Step 7: Provide migration back link and print migration complete message
echo "<div class='message success'>Migration process completed.</div>";
echo "<a href='" . getGoBackLink() . "' class='button'>Go back</a>";
?>
