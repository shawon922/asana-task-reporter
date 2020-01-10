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
                  ->orderBy('created_at', 'asc')
                  ->get()
                  ->toArray();
    
    $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject);
    $csv->setOutputBOM(\League\Csv\Writer::BOM_UTF8);
    $csv->insertOne($header);

    foreach ($messages as $message) { 
      $start = date('H:i', $message['start_time']);
      if (!empty($message['end_time'])) {
        $end = date('H:i', $message['end_time']);
        $interval = $message['end_time'] - $message['start_time'];
        $duration = sprintf('%0.2f', $interval / (60 * 60));
      } else {
        $end = '-';
        $duration = '-';
      }      

      $row = [
        'Name' => $message['account_name'],
        'Project' => $message['project_name'],
        'URL' => $message['task_url'],
        'Start' => $start,
        'End' => $end,
        'Time' => $duration
      ];
      
      $csv->insertOne($row);
    }
        
    return response((string) $csv, 200, [
      'Content-Type' => 'text/csv',
      'Content-Transfer-Encoding' => 'binary',
      'Content-Disposition' => 'attachment; filename="'.date('Ymd', strtotime($todayTsStart)).'.csv"',
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
