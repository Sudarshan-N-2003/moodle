<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

// 1. BASIC SETTINGS
$CFG->wwwroot   = getenv('MOODLE_WWWROOT') ?: 'https://neit-edu-in.onrender.com';
$CFG->dataroot  = getenv('MOODLE_DATAROOT') ?: '/var/www/moodledata';
$CFG->admin     = 'admin';

// CRITICAL FOR RENDER: 
// Render handles SSL at the load balancer. Moodle needs to know this 
// to generate https:// links correctly and avoid redirect loops.
$CFG->sslproxy = true; 

// Permissions: 0777 is often safer in Docker/Render to avoid "not writable" errors
$CFG->directorypermissions = 0777; 

// 2. DATABASE CONFIGURATION (Parses Render/Neon Connection String)
$database_url = getenv('DATABASE_URL');

if (empty($database_url)) {
    // Fallback for build steps where DB might not be present yet
    error_log("DATABASE_URL not set. Database config skipped.");
} else {
    $parts = parse_url($database_url);

    if ($parts === false) {
        die("Invalid DATABASE_URL provided.\n");
    }

    $dbhost = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? '';
    if ($port !== '') {
        $dbhost = $dbhost . ':' . $port;
    }

    $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : 'moodle';
    $dbuser = $parts['user'] ?? '';
    $dbpass = $parts['pass'] ?? '';

    // Parse query parameters (for sslmode etc)
    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    $CFG->dbtype    = 'pgsql';
    $CFG->dblibrary = 'native';
    $CFG->dbhost    = $dbhost;
    $CFG->dbname    = $dbname;
    $CFG->dbuser    = $dbuser;
    $CFG->dbpass    = $dbpass;
    $CFG->prefix    = 'mdl_';

    $CFG->dboptions = [
        'dbcollation' => 'utf8mb4_unicode_ci', // Modern standard
        'sslmode' => $query['sslmode'] ?? 'require',
    ];
    
    // Add specific settings for Neon/Supabase if required
    if (!empty($query['channel_binding'])) {
        $CFG->dboptions['channel_binding'] = $query['channel_binding'];
    }
}

// 3. FINAL SETUP
require_once(__DIR__ . '/lib/setup.php');
