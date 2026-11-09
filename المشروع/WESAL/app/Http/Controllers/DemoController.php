<?php

namespace App\Http\Controllers;

class DemoController extends Controller
{
    /**
     * The legacy demo endpoints can import SQL, drop every table, or replace
     * uploaded files. They are retired for production and intentionally fail
     * closed even when a stale DEMO_MODE setting is left enabled.
     */
    public function cron_1()
    {
        abort(404);
    }

    public function cron_2()
    {
        abort(404);
    }

    public function drop_all_tables()
    {
        abort(404);
    }

    public function import_demo_sql()
    {
        abort(404);
    }

    public function extract_uploads()
    {
        abort(404);
    }

    public function remove_folder()
    {
        abort(404);
    }
}
