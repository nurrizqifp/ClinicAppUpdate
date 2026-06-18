<?php
return [
    'up' => "ALTER TABLE patients ADD COLUMN nik VARCHAR(16) NULL UNIQUE AFTER user_id;",
    'down' => "ALTER TABLE patients DROP COLUMN nik;"
];
