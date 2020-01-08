<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

use App\ChatworkMessage;

class ReportController extends Controller
{
  private $chatworkApiUrl = 'https://api.chatwork.com/v2/';
  
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    date_default_timezone_set("Asia/Dhaka");
  }

  public function export(Request $request)
  {

    $header = ['Name',	'Project',	'URL',	'Start',	'End',	'Time'];
    $date = $request->get('date');

    if (!empty($date)) {
      $todayTsStart = strtotime($date . ' 00:00:00');
      $todayTsEnd = strtotime($date . ' 23:59:59');
    } else {
      $todayTsStart = strtotime('today');
      $todayTsEnd = strtotime('tomorrow') - 1;
    }
    
    $messages = ChatworkMessage::select([
                    'id', 
                    'account_id',
                    'account_name',
                    'task_id',
                    'task_status',
                    'project_name',
                    'task_url',
                    'send_time',
                  ])
                  ->where('send_time', '>=', $todayTsStart)->where('send_time', '<=', $todayTsEnd)
                  ->orderBy('account_id', 'asc')
                  ->orderBy('send_time', 'asc')
                  ->get()
                  ->toArray();
    
    $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject);
    $csv->setOutputBOM(\League\Csv\Writer::BOM_UTF8);
    $csv->insertOne($header);
    
    $report = [];

    foreach ($messages as $message) { 
      
      if (!isset($report[$message['account_id']][$message['task_id']])) {
        $report[$message['account_id']][$message['task_id']] = $message;
      }

      if (!isset($currentAccountId)) {
        $currentAccountId = $message['account_id'];
      } else if ($currentAccountId != $message['account_id']) { 
        foreach ($report[$currentAccountId] as $msg) { 
          $start = $end = '-';
          $taskStatus = strtolower($msg['task_status']);
          if ($taskStatus == 'start') {
            $start = date('H:i', $msg['send_time']);
          } else if ($taskStatus == 'end') {
            $end = date('H:i', $msg['send_time']);
          }
          
          $row = [
            'Name' => $msg['account_name'],
            'Project' => $msg['project_name'],
            'URL' => $msg['task_url'],
            'Start' => $start,
            'End' => $end,
            'Time' => '-'
          ];

          $csv->insertOne($row);
        } 

        unset($report[$currentAccountId]);
        $currentAccountId = $message['account_id'];
      }
      
      $taskStatus = strtolower($message['task_status']);
      $report[$message['account_id']][$message['task_id']][$taskStatus] = $message['send_time'];
      
      if (!empty($report[$message['account_id']][$message['task_id']]['start']) && !empty($report[$message['account_id']][$message['task_id']]['end'])) {
        $start = date('H:i', $report[$message['account_id']][$message['task_id']]['start']);
        $end = date('H:i', $report[$message['account_id']][$message['task_id']]['end']);
        $interval = $report[$message['account_id']][$message['task_id']]['end'] - $report[$message['account_id']][$message['task_id']]['start'];
        $duration = sprintf('%0.2f', $interval / (60 * 60));

        $row = [
          'Name' => $message['account_name'],
          'Project' => $message['project_name'],
          'URL' => $message['task_url'],
          'Start' => $start,
          'End' => $end,
          'Time' => $duration
        ];

        $csv->insertOne($row);

        unset($report[$message['account_id']][$message['task_id']]);
      }
    }
    
    return response((string) $csv, 200, [
      'Content-Type' => 'text/csv',
      'Content-Transfer-Encoding' => 'binary',
      'Content-Disposition' => 'attachment; filename="'.date('Ymd', $todayTsStart).'.csv"',
    ]);
  }

  public function getReport(Request $request)
  {
    $date = $request->get('date');
    $accountId = $request->get('account_id');

    if (!empty($date)) {
      $todayTsStart = $date . ' 00:00:00';
      $todayTsEnd = $date . ' 23:59:59';
    } else {
      $todayTsStart = date('Y-m-d 00:00:00');
      $todayTsEnd = date('Y-m-d 23:59:59');
    }

    $where = [
      ['updated_at', '>=', $todayTsStart],
      ['updated_at', '<=', $todayTsEnd]
    ];

    if (!empty($accountId)) {
      array_push($where, ['account_id', '=', $accountId]);
    }
    
    $messages = ChatworkMessage::select([
                    'id', 
                    'account_id',
                    'body',
                    'account_name',
                    'task_id',
                    'project_name',
                    'task_url',
                    'start_time',
                    'end_time',
                    'task_status',
                    'created_at',
                    'updated_at',
                  ])
                  ->where($where)
                  ->orderBy('account_id', 'asc')
                  ->orderBy('created_at', 'desc')
                  ->get()
                  ->toArray();
    
    return $messages;
  }
}
