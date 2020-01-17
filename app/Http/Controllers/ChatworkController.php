<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

use App\ChatworkMessage;

class ChatworkController extends Controller
{
  private $chatworkApiUrl = 'https://api.chatwork.com/v2/';
  private $chatworkRoomId = 38623685; // Tokyo BD room
  

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    date_default_timezone_set("Asia/Dhaka");
  }

  /**
   * Store a new message.
   *
   * @param  Request  $request
   * @return Response
   */
  public function storeMessage(Request $request)
  {
    $roomMembers = [
      321931 => "Takeshi Torigoe",
      369868 => "木下祥吾 Kinoshita Shogo",
      380070 => "灰野 広武 Hiromu Haino (Hiro)",
      592367 => "Koji Iinuma(KOJI)",
      1111461 => "布施基 Motoi Fuse",
      1304102 => "Misako Funabashi",
      1461056 => "Haruka Kinouchi",
      1532687 => "Munir Hossain(Munir)",
      1642025 => "Chitra Bonik (chitra)",
      1658746 => "Tahmina Naznin(Mini)",
      1773877 => "Utpal Biswas(UB)",
      1836326 => "Rie Harada",
      2280612 => "Sazib【サジブ】",
      2358469 => "Abu Rayhan",
      2362464 => "Fahreyad",
      2368066 => "出口祐香(Yuka)",
      2368068 => "菅野 真澄 Masumi Kanno",
      2368366 => "重田 紗希(SAKI)",
      2378874 => "Satoshi Suganami",
      2470772 => "Faysal",
      2575145 => "Muntasir Abdullah",
      2577141 => "Toma",
      2594500 => "M Mizan Ibn",
      2637992 => "Mamun",
      2648046 => "小野義貴 Yoshitaka Ono(YOSHI)",
      2697927 => "Rifat",
      2929099 => "平川 葵(Aoi)",
      2932816 => "Md Mahfuzur Rahman",
      2970521 => "Naoto Shimoda",
      3077725 => "M A Hakim",
      3078497 => "Md. Fahmid Al Masud",
      3078523 => "Ariful Islam",
      3207149 => "Mahamudul Hasan",
      3207388 => "Sharmin Manjur",
      3279720 => "Shelley Ferdousi (Shelley)",
      3327341 => "Mahmudul Islam Prakash",
      3371549 => "Sumon",
      3401812 => "sayed al momin",
      3433870 => "A B M Faruque Rahman",
      3548885 => "Jamil Hossain",
      3580278 => "Minhajul Russel (Russel)",
      3624438 => "Kanij",
      3643596 => "中村 知(motimoti)",
      3666083 => "Evan Khan(EK)",
      3724599 => "Syed Mazhar Ahmed",
      3766845 => "大江紗月",
      3807338 => "Wataru Tajima(Watta-)",
      3878206 => "Fuad Hassan",
      3967030 => "satsuki oe",
      4012839 => "a_nakanishi",
      4060636 => "seongkyu Park(ソンギュ)",
      4098158 => "Oyama Keigo(KG)",
      4238832 => "Wataru Tajima",
      4373344 => "Sadia naushin",
    ];

    try {
      $data = $request->input('webhook_event');

      if (!empty($data['body'])) {
        
        $rawMessage = trim($data['body']);

        if (Str::startsWith($rawMessage, '[info]') && Str::endsWith($rawMessage, '[/info]')) {
          $parsedMessage = trim(rtrim(ltrim($rawMessage, '/\[info\]/'), '/\[\/info\]/'));
          $messageArr = explode(',', $parsedMessage);

          if (count($messageArr) == 3) {
            list($taskStatus, $projectName, $taskUrl) = $messageArr;
            $taskUrl = rtrim($taskUrl, '/f');
            $taskUrlSections = explode('/', $taskUrl);
            $len = count($taskUrlSections);
            $taskId = 0;
            
            if ($len > 0) {
              $taskId = $taskUrlSections[$len-1];
            }

            $taskStatus = strtolower(trim($taskStatus));

            if ($taskStatus === 'start') { 
              if (isset($data['update_time']) && $data['update_time'] != 0) {
                $message = ChatworkMessage::where([
                    ['message_id', '=', $data['message_id']],
                    ['account_id', '=', $data['account_id']],
                  ])
                  ->orderBy('id', 'desc')
                  ->first();
              }              

              if (empty($message)) {
                $message = new ChatworkMessage;
                $message->start_time = !empty($data['update_time']) ? $data['update_time'] : $data['send_time'];
                $message->message_id = $data['message_id'];
                $message->end_time = 0;
                $message->room_id = $data['room_id'];
                $message->account_id = $data['account_id'];
                $message->account_name = isset($roomMembers[$data['account_id']]) ? $roomMembers[$data['account_id']] : '-';
              }

              $message->body = $rawMessage;
              $message->task_id = $taskId;
              $message->project_name = $projectName;
              $message->task_url = $taskUrl;
              
            } else if ($taskStatus === 'end') {
              $message = ChatworkMessage::where([
                ['task_status', '=', 'start'],
                ['account_id', '=', $data['account_id']],
                ['task_id', '=', $taskId],
              ])
              ->orderBy('id', 'desc')
              ->first();
              
              if (empty($message)) {
                return;
              }

              $message->end_time = $data['send_time'];

            } else {
              return;
            }

            $message->task_status = $taskStatus;
            $message->update_time = $data['update_time'];

            $message->save();
          }
        
        }
      }
      
    } catch (\Exception $e) {
    }
    
    return isset($message) ? $message : null;
  }

  public function profile(Request $request)
  {
    $chatworkUrl = $this->chatworkApiUrl;
    
    $token = $request->header('X-ChatWorkToken');
    
    $client = new Client(['base_uri' => $chatworkUrl, 'headers' => [
        'Accept' => 'application/json',
        'X-ChatWorkToken' => $token,
      ]
    ]);
    
    $url = 'me';
    $response = $client->request('GET', $url);

    return $response->getBody();
  }

  public function postMessage(Request $request)
  {
    $chatworkUrl = $this->chatworkApiUrl;
    $roomId = $this->chatworkRoomId;
    $data = $request->input('data');
    $token = $data['chatwork_token'];
    
    $payload = [
      'body' => $data['body'],
    ];
    $client = new Client(['base_uri' => $chatworkUrl]);
    $url = 'rooms/'. $roomId .'/messages';
    $response = $client->request('POST', $url, [
      'form_params' => $payload,
      'headers' => [
        'X-ChatWorkToken' => $token,
        'Content-Type' => 'application/x-www-form-urlencoded',
      ]
    ]);

    return $response->getBody();
  }

  public function getReport(Request $request)
  {
    $chatworkUrl = $this->chatworkApiUrl;
    
    $token = $request->header('X-ChatWorkToken');
    
    $client = new Client(['base_uri' => $chatworkUrl, 'headers' => [
        'Accept' => 'application/json',
        'X-ChatWorkToken' => $token,
      ]
    ]);
    
    $url = 'me';
    $response = $client->request('GET', $url);

    return $response->getBody();
  }
}
