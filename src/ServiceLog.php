<?php

namespace Service\Logging;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\UnauthorizedException;

class ServiceLog
{
    protected $auditFolderPath  =   'audit/csv';

    /**
     * The path in storage/app folder
     * @param string $path
     */
    public function setPath(string $path = 'audit/csv')
    {
        $this->auditFolderPath  =   $path;
    }

    public function getPath()
    {
        return $this->auditFolderPath;
    }

    public function writeLog(string $tenantId, string $event, string $by, bool $status, string $url)
    {
        $tenantStorage  =   $this->getPath().'/'.$tenantId;

        Storage::makeDirectory($tenantStorage); // Create directory if not exist

        $fileName   =   date('Y-m').'.csv'; // csv file by year-month format
        $csvRealPath    =   storage_path('app/'.$tenantStorage).'/'.$fileName; // csv file path

        // Rewrite a new file
        $writeCsv = fopen($csvRealPath, 'w');

        $fields =   [
            $event,
            $by,
            Carbon::now(),
            $status ? 1 : 0,
            $url,
            $this->getUserIp(),
            $this->getUserAgent()
        ];

        fputcsv($writeCsv, $fields, '`');

        fclose($writeCsv);

        return true;
    }

    public function getLog(string $tenantId, string $month, string $year)
    {
        $tenantStorage  =   $this->getPath().'/'.$tenantId;

        Storage::makeDirectory($tenantStorage); // Create directory if not exist

        $path    =   storage_path('app/'.$tenantStorage).'/'.$year.'-'.$month.'.csv'; // csv file path

        $lines  =   [];
        if (file_exists($path)) {
            $usersFile = fopen($path, 'r+');

            if ($usersFile)
            {
                while (!feof($usersFile) ) {
                    $lines[] = fgetcsv($usersFile, 0 , '`');
                }

            }
            fclose($usersFile);
        }

        return $lines;
    }

    /**
     * Get user IP address
     * @return mixed|string
     */
    protected function getUserIp()
    {
        //whether ip is from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from remote address
        else
        {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }

        if (empty($ipAddress))
        {
            $ipAddress =   '-';
        }

        return $ipAddress;
    }

    /**
     * Get user agent
     * @return mixed|string
     */
    protected function getUserAgent()
    {
        $agent  =   '-';

        if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT']))
        {
            $agent  =   $_SERVER['HTTP_USER_AGENT'];
        }

        return $agent;
    }

}
