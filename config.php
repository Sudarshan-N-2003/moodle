cd C:\Users\VVIT-CS\Downloads\moodle-latest-501\moodle

# Write config.php (overwrites if exists)
@"
<?php
// Moodle config.php that reads DATABASE_URL and other values from environment.
// Safe to commit because it reads secrets from environment variables.

unset($CFG);
global $CFG;
$CFG = new stdClass();

/* Basic paths */
$CFG->wwwroot   = getenv('MOODLE_WWWROOT') ?: 'https://neit-edu-in.onrender.com';
$CFG->dataroot  = getenv('MOODLE_DATAROOT') ?: '/var/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0755;

/* Read DATABASE_URL from environment (Neon-style) */
$database_url = getenv('DATABASE_URL');
if (empty($database_url)) {
    die("DATABASE_URL environment variable is not set.\n");
}
$parts = parse_url($database_url);
if ($parts === false) {
    die("Invalid DATABASE_URL.\n");
}

/* Host and port */
$dbhost = $parts['host'] ?? 'localhost';
$port = $parts['port'] ?? '';
if ($port !== '') {
    $dbhost = $dbhost . ':' . $port;
}

/* DB name, user, pass */
$dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : 'moodle';
$dbuser = $parts['user'] ?? '';
$dbpass = $parts['pass'] ?? '';

/* Parse query string for sslmode and channel_binding */
$query = [];
if (isset($parts['query'])) {
    parse_str($parts['query'], $query);
}

/* Assign Moodle DB config */
$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = $dbhost;
$CFG->dbname    = $dbname;
$CFG->dbuser    = $dbuser;
$CFG->dbpass    = $dbpass;
$CFG->prefix    = 'mdl_';

/* dboptions — ensure SSL and channel binding for Neon */
$dboptions = [
    'dbcollation' => 'utf8',
    'sslmode' => $query['sslmode'] ?? 'require',
];
if (!empty($query['channel_binding'])) {
    $dboptions['channel_binding'] = $query['channel_binding'];
}
$CFG->dboptions = $dboptions;

/* Finish */
require_once(__DIR__ . '/lib/setup.php');
?>
"@ > config.php
