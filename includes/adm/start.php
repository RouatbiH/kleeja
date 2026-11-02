<?php
/**
 *
 * @package adm
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

// not for directly open
if (!defined('IN_ADMIN')) {
    exit();
}

//style of
$stylee = 'admin_start';
$h_lst_files = basename(ADMIN_PATH) . '?cp=c_files&amp;last_visit=';
$h_lst_imgs = basename(ADMIN_PATH) . '?cp=d_img_ctrl&amp;last_visit=';
$current_smt = preg_replace('/[^a-z0-9_]/i', '', g('smt', default: 'general'));
$GET_FORM_KEY = kleeja_add_form_key_get('adm_start_actions');

//data
$lst_reg = empty($stat_last_user) ? $lang['UNKNOWN'] : kleeja_html_display($stat_last_user);
$files_number = $stat_files + $stat_imgs;
$files_sizes = readable_size($stat_sizes);
$users_number = $stat_users;
$last_del_fles = (int) $config['del_f_day'] <= 0 ? $lang['CLOSED_FEATURE'] : kleeja_date($stat_last_f_del);
$php_version = isset($NO_PHPINFO) || !function_exists('phpinfo') ? phpversion() : 'PHP ' . phpversion();
$mysql_version = $SQL->server_info();
$max_execution_time = function_exists('ini_get') ? @ini_get('max_execution_time') : @get_cfg_var('max_execution_time');
$upload_max_filesize = function_exists('ini_get')
    ? @ini_get('upload_max_filesize')
    : @get_cfg_var('upload_max_filesize');
$post_max_size = function_exists('ini_get') ? @ini_get('post_max_size') : @get_cfg_var('post_max_size');
$memory_limit = function_exists('ini_get') ? @ini_get('memory_limit') : @get_cfg_var('memory_limit');
$s_last_google = (int) $stat_last_google === 0 ? '[ ? ]' : kleeja_date($stat_last_google);
$s_google_num = $stat_google_num;
$s_last_bing = (int) $stat_last_bing === 0 ? '[ ? ]' : kleeja_date($stat_last_bing);
$s_bing_num = $stat_bing_num;
$usernamelang = sprintf($lang['KLEEJA_CP_W'], $username);
$current_year = date('Y');

$startBoxes = [
    'notifications' => ['title' => $lang['NOTIFICATIONS'], 'hidden' => (int) adm_is_start_box_hidden('notifications')],
    'statsBoxes' => ['title' => $lang['STATS_BOXES'], 'hidden' => (int) adm_is_start_box_hidden('statsBoxes')],
    'lastVisitActions' => [
        'title' => $lang['LAST_VISIT'],
        'hidden' => (int) adm_is_start_box_hidden('lastVisitActions'),
    ],
    'statsChart' => ['title' => $lang['STATS'], 'hidden' => (int) adm_is_start_box_hidden('statsChart')],
    'hurryActions' => ['title' => $lang['HURRY_HURRY'], 'hidden' => (int) adm_is_start_box_hidden('hurryActions')],
    'extraStats' => ['title' => $lang['OTHER_INFO'], 'hidden' => (int) adm_is_start_box_hidden('extraStats')],
];

$extra_adm_start_html = '';

//size board by percent
$per = $stat_sizes / ($config['total_size'] * 1048576);
$per1 = round($per * 100, 2);
$per1 = $per1 >= 100 ? 100 : $per1;

//ppl must know about kleeja version!
$kleeja_version =
    '<a href="' .
    basename(ADMIN_PATH) .
    '?cp=p_check_update" onclick="javascript:get_kleeja_link(this.href, \'#content\'); return false;" title="' .
    $lang['R_CHECK_UPDATE'] .
    '">' .
    KLEEJA_VERSION .
    '</a>';

//admin messages system
$ADM_NOTIFICATIONS = [];

//using Internet Explorer ! the control panel doesn't support any version of it
if (is_browser('ie')) {
    $ADM_NOTIFICATIONS['IE6'] = [
        'id' => 'IE6',
        'msg_type' => 'error',
        'title' => $lang['NOTE'],
        'msg' => $lang['ADMIN_USING_IE6'],
    ];
}

//if upgrading from 1rc6 to 1.0, some files must be deleted !
if (file_exists(PATH . 'includes/adm/files.php') || file_exists(PATH . 'admin.php')) {
    $ADM_NOTIFICATIONS['old_files'] = [
        'id' => 'old_files',
        'msg_type' => 'info',
        'title' => $lang['NOTE'],
        'msg' => $lang['ADM_UNWANTED_FILES'],
    ];
}

//if html url is enabled but .htaccess is not available in the root dir !
if (!file_exists(PATH . '.htaccess') && (int) $config['mod_writer'] === 1) {
    $ADM_NOTIFICATIONS['htmlurlshtaccess'] = [
        'id' => 'htmlurlshtaccess',
        'msg_type' => 'info',
        'title' => $lang['NOTE'],
        'msg' => $lang['HTML_URLS_ENABLED_NO_HTCC'],
    ];
}

//updating
$v = @unserialize($config['new_version'], ['allowed_classes' => false]);
//empty before the first check, and the check saves no version when GitHub can't be reached
$latest_version = (string) ($v['version_number'] ?? '');

if ($latest_version !== '' && version_compare(strtolower(KLEEJA_VERSION), strtolower($latest_version), '<')) {
    $ADM_NOTIFICATIONS['up_ver_klj'] = [
        'id' => 'up_ver_klj', //this not so important row
        'msg_type' => 'error',
        'title' => $lang['R_CHECK_UPDATE'],
        'msg' =>
            sprintf($lang['UPDATE_NOW_S'], KLEEJA_VERSION, $latest_version) .
            '<br />' .
            '<a href="https://kleeja.net/" target="_blank">kleeja.net</a>',
    ];

    extract(runHook('admin_update_now', get_defined_vars()));
}

//the database is older than the files, an update stopped before it, the founders can finish it
if ((int) ($config['db_version'] ?? 0) < (int) KLEEJA_DB_VERSION && (int) $userinfo['founder'] === 1) {
    $ADM_NOTIFICATIONS['db_update'] = [
        'id' => 'db_update',
        'msg_type' => 'error',
        'title' => $lang['DB_UPDATE_NEEDED_TITLE'],
        'msg' =>
            sprintf($lang['DB_UPDATE_NEEDED'], (int) ($config['db_version'] ?? 0), KLEEJA_DB_VERSION) .
            '<br /><a class="btn btn-sm btn-primary mt-2" href="' .
            basename(ADMIN_PATH) .
            '?cp=p_check_update&amp;smt=db_update&amp;' .
            kleeja_add_form_key_get('UPDATER_FORM_KEY') .
            '">' .
            $lang['DB_UPDATE_NOW'] .
            '</a>',
    ];
}

extract(runHook('default_admin_page', get_defined_vars()));

//check upload_max_filesize
if (!empty($d_groups) && is_array($d_groups)) {
    $biggest_size = 0;

    foreach ($d_groups as $gid => $gdata) {
        if (!empty($d_groups[$gid]['exts']) && is_array($d_groups[$gid]['exts'])) {
            $u_e_s = $d_groups[$gid]['exts'];
            arsort($u_e_s);

            if (!empty($u_e_s)) {
                $current_size = array_shift($u_e_s);
                $biggest_size = $current_size > $biggest_size ? $current_size : $biggest_size;
            }
        }
    }

    if (strpos($upload_max_filesize, 'M') !== false) {
        $upload_max_filesize_s = ((int) trim(str_replace('M', '', $upload_max_filesize))) * 1048576;
    } elseif (strpos($upload_max_filesize, 'G') !== false) {
        $upload_max_filesize_s = ((int) trim(str_replace('G', '', $upload_max_filesize))) * 1073741824;
    } else {
        $upload_max_filesize_s = (int) $upload_max_filesize;
    }

    if (!empty($upload_max_filesize) && $upload_max_filesize_s < $biggest_size) {
        $ADM_NOTIFICATIONS['file_size_ini_low'] = [
            'id' => 'file_size_ini_low',
            'msg_type' => 'info',
            'title' => $lang['NOTE'],
            'msg' => sprintf(
                $lang['PHPINI_FILESIZE_SMALL'],
                readable_size($biggest_size),
                readable_size($upload_max_filesize_s),
            ),
        ];
    }

    //check post_max_size
    if (strpos($post_max_size, 'M') !== false) {
        $post_max_size_s = ((int) trim(str_replace('M', '', $post_max_size))) * 1048576;
    } elseif (strpos($post_max_size, 'G') !== false) {
        $post_max_size_s = ((int) trim(str_replace('G', '', $post_max_size))) * 1073741824;
    } else {
        $post_max_size_s = (int) $post_max_size;
    }

    $post_max_size_s_must_be = $config['filesnum'] * $biggest_size + 5242880; //+ 5 mega to make sure it's ok

    if (!empty($post_max_size) && $post_max_size_s < $post_max_size_s_must_be) {
        $ADM_NOTIFICATIONS['post_m_size_ini_low'] = [
            'id' => 'post_m_size_ini_low',
            'msg_type' => 'info',
            'title' => $lang['NOTE'],
            'msg' => sprintf(
                $lang['PHPINI_MPOSTSIZE_SMALL'],
                $config['filesnum'],
                readable_size($post_max_size_s_must_be),
            ),
        ];
    }
}

//
// if 10 days, lets check again !
// rev: let's say cache is not refreshed, so we will redirect alots of time,
// so update_done will be good solution
//
if ((empty($v['last_check']) || time() - $v['last_check'] > 3600 * 24 * 10) && !ig('update_done')) {
    redirect(basename(ADMIN_PATH) . '?cp=p_check_update&amp;show_msg=1');
    $SQL->close();

    exit();
}

//if config not safe
if (function_exists('fileperms') && !defined('KLEEJA_NO_CONFIG_CHECK') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    if ((bool) (@fileperms(PATH . KLEEJA_CONFIG_FILE) & 0x0002)) {
        $ADM_NOTIFICATIONS['config_perm'] = [
            'id' => 'config_perm',
            'msg_type' => 'info',
            'title' => $lang['NOTE'],
            'msg' => $lang['CONFIG_WRITEABLE'],
        ];
    }
}

//no htaccess
if (!file_exists(PATH . $config['foldername'] . '/.htaccess')) {
    $ADM_NOTIFICATIONS['htaccess_u'] = [
        'id' => 'htaccess_u',
        'msg_type' => 'error',
        'title' => $lang['WARN'],
        'msg' => sprintf($lang['NO_HTACCESS_DIR_UP'], $config['foldername']),
    ];
}

if (!file_exists(PATH . $config['foldername'] . '/thumbs/.htaccess')) {
    $ADM_NOTIFICATIONS['htaccess_t'] = [
        'id' => 'htaccess_t',
        'msg_type' => 'error',
        'title' => $lang['WARN'],
        'msg' => sprintf($lang['NO_HTACCESS_DIR_UP_THUMB'], $config['foldername'] . '/thumbs'),
    ];
}

//there is cleaning files process now
if ((int) $config['klj_clean_files_from'] > 0) {
    $ADM_NOTIFICATIONS['klj_clean_files'] = [
        'id' => 'klj_clean_files',
        'msg_type' => 'info',
        'title' => $lang['NOTE'],
        'msg' => $lang['T_CLEANING_FILES_NOW'],
    ];
}

//if there is no thumbs folder
if (!file_exists(PATH . $config['foldername'] . '/thumbs') && (int) $config['thumbs_imgs'] !== 0) {
    $ADM_NOTIFICATIONS['no_thumbs'] = [
        'id' => 'no_thumbs',
        'msg_type' => 'info',
        'title' => $lang['NOTE'],
        'msg' => sprintf($lang['NO_THUMB_FOLDER'], PATH . $config['foldername'] . '/thumbs'),
    ];
}

//is there copyrights for translator ?
$translator_copyrights = isset($lang['S_TRANSLATED_BY']) ? $lang['S_TRANSLATED_BY'] : false;

//secondary menu
$go_menu = [
    'general' => [
        'name' => $lang['GENERAL_STAT'],
        'link' => basename(ADMIN_PATH) . '?cp=start&amp;smt=general',
        'goto' => 'general',
        'current' => $current_smt === 'general',
    ],
    'other' => [
        'name' => $lang['OTHER_INFO'],
        'link' => basename(ADMIN_PATH) . '?cp=start&amp;smt=other',
        'goto' => 'other',
        'current' => $current_smt === 'other',
    ],
    'blog' => [
        'name' => $lang['ADM_KLEEJA_BLOG'],
        'link' => basename(ADMIN_PATH) . '?cp=start&amp;smt=blog',
        'goto' => 'blog',
        'current' => $current_smt === 'blog',
    ],
    'team' => [
        'name' => $lang['KLEEJA_TEAM'],
        'link' => basename(ADMIN_PATH) . '?cp=start&amp;smt=team',
        'goto' => 'team',
        'current' => $current_smt === 'team',
    ],
];

// is there a last visit of images and files ?
$files_last_visit = filter_exists('f_lastvisit', 'filter_uid', 'lastvisit', $userinfo['id'])
    ? get_filter('f_lastvisit', 'lastvisit', just_value: true, user_id: $userinfo['id'])
    : false;
$image_last_visit = filter_exists('i_lastvisit', 'filter_uid', 'lastvisit', $userinfo['id'])
    ? get_filter('i_lastvisit', 'lastvisit', just_value: true, user_id: $userinfo['id'])
    : false;

//hurry, hurry section, get languages
$hurry_lang_link = basename(ADMIN_PATH) . '?cp=g_users&amp;smt=group_data&amp;' . $GET_FORM_KEY . '&amp;lang_change=';
$hurry_langs_list = '';

if ($dh = @opendir(PATH . 'lang')) {
    while (($file = @readdir($dh)) !== false) {
        if (strpos($file, '.') === false && $file !== '..' && $file !== '.') {
            $hurry_langs_list .=
                '<option value="' .
                htmlspecialchars($file) .
                '"' .
                ($d_groups[$config['default_group']]['configs']['language'] === $file ? ' selected="selected"' : '') .
                '>' .
                $file .
                '</option>';
        }
    }
    @closedir($dh);
}

$hurry_groups_list = '<option value="-1" selected="selected">' . $lang['ALL'] . '</option>';
$hurry_groups_list .= '<option value="' . $config['default_group'] . '">' . $lang['DEFAULT_GROUP'] . '</option>';

foreach ($d_groups as $id => $ddt) {
    $hurry_groups_list .=
        '<option value="' .
        $id .
        '">' .
        str_replace(
            ['{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'],
            [$lang['ADMINS'], $lang['USERS'], $lang['GUESTS']],
            $d_groups[$id]['data']['group_name'],
        ) .
        '</option>';
}

//hurry, hurry section, links
$del_cache_link =
    basename(ADMIN_PATH) . '?cp=r_repair&amp;case=clearc&amp;' . kleeja_add_form_key_get('REPAIR_FORM_KEY');

// get stats filter so we can draw a chart for the user
$stats_chart = false;

$cf_query = [
    'SELECT' => 'f.filter_uid, f.filter_value, f.filter_time',
    'FROM' => "{$dbprefix}filters f",
    'WHERE' => "f.filter_type = 'stats_for_acp'",
    'ORDER BY' => 'f.filter_time DESC',
];

$cf_result = $SQL->build($cf_query);
$cf_num = $SQL->num_rows($cf_result);

if ($cf_num > 3) {
    $stats_chart = 'arrayOfDataMulti = new Array(';

    $comma = false;
    //get currently right now stats
    $prv_files = get_actual_stats('files');
    $prev_imgs = get_actual_stats('imgs');
    $prev_date = date('d-n-Y');
    $todayIsGone = false;

    while ($row = $SQL->fetch_array($cf_result)) {
        //jump today
        if ($prev_date === $row['filter_uid']) {
            continue;
        }

        //get this row data
        [$s_files, $s_imgs, $s_sizes] = explode(':', $row['filter_value']);

        $t_files = $prv_files - $s_files;
        $t_imgs = $prev_imgs - $s_imgs;

        if (date('d-n-Y') === $prev_date) {
            $day = $lang['TODAY'] . ' ~ ' . $lang['NOW'];

            if ($todayIsGone) {
                continue;
            }

            $todayIsGone = true;
        } else {
            $day = $prev_date;
        }

        $stats_chart .=
            ($comma ? ',' : '') .
            "[[$t_files,$t_imgs],'" .
            ($cf_num > 6 ? str_replace(date('-Y'), '', $day) : $day) .
            "']";

        $comma = true;
        $prv_files = $s_files;
        $prev_imgs = $s_imgs;
        $prev_date = $row['filter_uid'];
    }

    $stats_chart .= ');';

    extract(runHook('stats_start_admin', get_defined_vars()));

    $SQL->freeresult($cf_result);

    //clean old chart stats
    if ($cf_num > 10) {
        $query_del = [
            'DELETE' => "{$dbprefix}filters",
            'WHERE' => "filter_type = 'stats_for_acp' AND filter_time < :time",
            'BIND' => ['time' => time() - 3600 * 24 * 10],
        ];
        $SQL->build($query_del);
    }
}

//other info page, what the server gives Kleeja, and a report to paste in a bug report
if ($current_smt === 'other') {
    $ini_value = fn(string $name): string => (string) (function_exists('ini_get')
        ? @ini_get($name)
        : @get_cfg_var($name));
    $ini_is_on = fn(string $name): bool => in_array(strtolower($ini_value($name)), ['1', 'on', 'true', 'yes'], true);

    //installation
    $db_version = (int) ($config['db_version'] ?? 0);
    $kleeja_db_version = (int) KLEEJA_DB_VERSION;
    $db_update_needed = $db_version < $kleeja_db_version;
    $latest_kleeja_version = $latest_version === '' ? $lang['UNKNOWN'] : htmlspecialchars($latest_version);
    $last_update_check = empty($v['last_check']) ? $lang['UNKNOWN'] : kleeja_date((int) $v['last_check']);
    $default_style = kleeja_html_display($config['style']);
    $mod_writer_on = (int) $config['mod_writer'] === 1;
    $siteclose_on = (int) $config['siteclose'] === 1;
    $dev_stage_on = defined('DEV_STAGE');

    $plugins_result = $SQL->build([
        'SELECT' => 'p.plg_name, p.plg_ver, p.plg_disabled',
        'FROM' => "{$dbprefix}plugins p",
        'ORDER BY' => 'p.plg_name',
    ]);

    $plugins_total = 0;
    $plugins_enabled = [];

    while ($row = $SQL->fetch_array($plugins_result)) {
        $plugins_total++;

        if ((int) $row['plg_disabled'] === 0) {
            $plugins_enabled[] = $row['plg_name'] . ' ' . $row['plg_ver'];
        }
    }

    $SQL->freeresult($plugins_result);
    $plugins_count = sprintf($lang['ADM_PLUGINS_ENABLED_OF'], count($plugins_enabled), $plugins_total);

    //server
    $os_name = function_exists('php_uname') ? php_uname('s') . ' ' . php_uname('r') : PHP_OS_FAMILY;
    $server_software = (string) ($_SERVER['SERVER_SOFTWARE'] ?? '');
    $web_server = $server_software === '' ? $lang['UNKNOWN'] : htmlspecialchars($server_software);
    $php_sapi = PHP_SAPI;

    if ($SQL->driver === 'sqlite') {
        $db_bytes = @filesize(PATH . $dbname);
    } else {
        //some hosts hide information_schema, then the size is unknown instead of an SQL error page
        $show_errors = $SQL->show_errors;
        $SQL->show_errors = false;
        $db_result = $SQL->build([
            'SELECT' => 'SUM(data_length + index_length) AS size',
            'FROM' => 'information_schema.TABLES',
            'WHERE' => 'table_schema = DATABASE() AND LEFT(table_name, :length) = :prefix',
            'BIND' => ['length' => strlen($dbprefix), 'prefix' => $dbprefix],
        ]);
        $SQL->show_errors = $show_errors;

        $db_row = $db_result ? $SQL->fetch_array($db_result) : false;
        $db_bytes = $db_row && is_numeric($db_row['size']) ? (int) $db_row['size'] : false;
        $SQL->freeresult($db_result);
    }

    $db_size = $db_bytes === false ? $lang['UNKNOWN'] : readable_size((int) $db_bytes);

    //php.ini, with a hint where a value is lower than what Kleeja settings need
    $file_uploads_on = $ini_is_on('file_uploads');
    $max_file_uploads = $ini_value('max_file_uploads');
    $max_input_time = $ini_value('max_input_time');
    $allow_url_fopen_on = $ini_is_on('allow_url_fopen');

    $file_uploads_hint = $file_uploads_on ? '' : $lang['ADM_INI_UPLOADS_OFF'];
    $max_file_uploads_hint =
        (int) $max_file_uploads > 0 && (int) $max_file_uploads < (int) $config['filesnum']
            ? sprintf($lang['ADM_INI_FILES_LOW'], (int) $config['filesnum'])
            : '';
    $upload_max_filesize_hint = isset($ADM_NOTIFICATIONS['file_size_ini_low'])
        ? sprintf($lang['ADM_INI_UPLOAD_LOW'], readable_size($biggest_size))
        : '';
    $post_max_size_hint = isset($ADM_NOTIFICATIONS['post_m_size_ini_low'])
        ? sprintf($lang['ADM_INI_POST_LOW'], readable_size($post_max_size_s_must_be), (int) $config['filesnum'])
        : '';

    //a missing extension is only a problem as big as the feature it stops, level is the badge color then
    $php_extensions = [
        [
            'name' => 'GD',
            'use' => $lang['ADM_EXT_GD'],
            'ok' => function_exists('imagecreatetruecolor'),
            'level' => 'danger',
        ],
        [
            'name' => 'Imagick',
            'use' => $lang['ADM_EXT_IMAGICK'],
            'ok' => (bool) phpversion('imagick'),
            'level' => 'neutral',
        ],
        ['name' => 'Zip', 'use' => $lang['ADM_EXT_ZIP'], 'ok' => class_exists('ZipArchive'), 'level' => 'warning'],
        [
            'name' => 'cURL',
            'use' => $lang['ADM_EXT_CURL'],
            'ok' => function_exists('curl_init'),
            'level' => $allow_url_fopen_on ? 'neutral' : 'warning',
        ],
        [
            'name' => 'Fileinfo',
            'use' => $lang['ADM_EXT_FILEINFO'],
            'ok' => function_exists('finfo_open') || function_exists('mime_content_type'),
            'level' => 'warning',
        ],
        [
            'name' => 'mbstring',
            'use' => $lang['ADM_EXT_MBSTRING'],
            'ok' => function_exists('mb_substr'),
            'level' => 'warning',
        ],
        [
            'name' => 'OPcache',
            'use' => $lang['ADM_EXT_OPCACHE'],
            'ok' => function_exists('opcache_get_status') && $ini_is_on('opcache.enable'),
            'level' => 'neutral',
        ],
    ];

    //folders that Kleeja writes to
    $kleeja_folders = [];

    foreach (
        [
            'cache' => ['use' => $lang['ADM_DIR_CACHE'], 'level' => 'danger'],
            $config['foldername'] => ['use' => $lang['ADM_DIR_UPLOADS'], 'level' => 'danger'],
            $config['foldername'] . '/thumbs' => ['use' => $lang['ADM_DIR_THUMBS'], 'level' => 'danger'],
            'plugins' => ['use' => $lang['ADM_DIR_PLUGINS'], 'level' => 'warning'],
            'styles' => ['use' => $lang['ADM_DIR_STYLES'], 'level' => 'warning'],
        ]
        as $folder => $folder_data
    ) {
        $kleeja_folders[] = ['path' => $folder, 'ok' => is_writable(PATH . $folder)] + $folder_data;
    }

    $disk_free = function_exists('disk_free_space') ? @disk_free_space(PATH . $config['foldername']) : false;
    $disk_free_space = $disk_free === false ? $lang['UNKNOWN'] : readable_size((int) $disk_free);

    extract(runHook('other_info_start_admin', get_defined_vars()));

    //plain text in English, like the bug report template of GitHub asks for it
    $missing_extensions = array_column(
        array_filter($php_extensions, fn(array $extension): bool => !$extension['ok']),
        'name',
    );
    $not_writable_folders = array_column(
        array_filter($kleeja_folders, fn(array $folder): bool => !$folder['ok']),
        'path',
    );

    $system_report = htmlspecialchars(
        implode("\n", [
            '- Kleeja: ' . KLEEJA_VERSION . ' (database ' . $db_version . ')',
            '- OS: ' . $os_name,
            '- Web server: ' . ($server_software === '' ? 'unknown' : $server_software),
            '- PHP: ' . PHP_VERSION . ' (' . PHP_SAPI . ')',
            '- Database: ' . $mysql_version,
            '- Style: ' . kleeja_html_decode($config['style']),
            '- Plugins: ' . (empty($plugins_enabled) ? 'none' : implode(', ', $plugins_enabled)),
            '- Missing PHP extensions: ' . (empty($missing_extensions) ? 'none' : implode(', ', $missing_extensions)),
            '- Not writable: ' . (empty($not_writable_folders) ? 'none' : implode(', ', $not_writable_folders)),
            '- php.ini: ' .
            implode(', ', [
                'file_uploads=' . ($file_uploads_on ? 'On' : 'Off'),
                'max_file_uploads=' . $max_file_uploads,
                'upload_max_filesize=' . $upload_max_filesize,
                'post_max_size=' . $post_max_size,
                'max_execution_time=' . $max_execution_time,
                'memory_limit=' . $memory_limit,
            ]),
            '- Mod Rewrite: ' . ($mod_writer_on ? 'on' : 'off'),
        ]),
    );
}

// ECharts draws the uploads chart on the dashboard and the commit charts on the team page
$start_charts = $current_smt === 'team' || ($current_smt === 'general' && $stats_chart);
