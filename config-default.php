<?php

define ('PENUB_DB', 'mysql://user:password@server/db_name');

define ('PENUB_KERNEL_PATH', 'penub');
define ('PENUB_CONTRIB_PATH', 'penub-contrib');
define ('PENUB_CUSTOM_CODE_PATH', 'custom');
define ('PENUB_FILES_FOLDER_PATH', 'files');

define ('PENUB_LOG_LEVEL_NOTICE',  1);
define ('PENUB_LOG_LEVEL_DEBUG',   2);
define ('PENUB_LOG_LEVEL_INFO',    3);
define ('PENUB_LOG_LEVEL_CRUCIAL', 4);
define ('PENUB_DFLT_LOG_LEVEL',    PENUB_LOG_LEVEL_DEBUG); //Switch to PENUB_LOG_LEVEL_CRUCIAL in production!