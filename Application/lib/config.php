<?php

namespace config\database
{
    const DSN = "pgsql:host=localhost;dbname=loggit";
    const USER = "loggit";
    const PASSWORD = "like%froggit";
}

namespace config
{
    const FETCH_PAGE_SIZE = 100;
    const MAX_LOG_FILE_SIZE = 4 * 1024 * 1024;
    const MAX_LOG_LINES = 400000;
    const MAX_LOG_LINE_LENGTH = 4096;
    const LOG_COMMIT_INTERVAL = 25000;
    const TIMEZONE_NAME = "Europe/Warsaw";
}