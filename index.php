<?php
use React\EventLoop\Loop;

require __DIR__ . '/vendor/autoload.php';
$ip='127.0.0.1';
$port=2113;
$server = stream_socket_server("tcp://$ip:$port");
stream_set_blocking($server, false);

$clients=[];
    Loop::addReadStream($server,function($server) use (&$clients){
        $client= stream_socket_accept($server);
        stream_set_blocking($client, false);
        $clients[]=$client;//yeni gelen oyuncuyu clients dizisine ekliyoruz
        echo "Yeni bir oyuncu geldi\n";
        $json=json_encode([
            'islem' => 'chat',
            'chat' => 'SERVERA HOŞGELDİN :) SERVER SAATİ:'.date('H:i:s'),
        ]);
        fwrite($client,$json);

        //oyuncudan gelen datayı oku
        Loop::addReadStream($client,function($client) use (&$clients){
            $data=fread($client,1024);//gelen buffer
            echo "\033[2J";//ekranı temizle
            print_r($clients);

            foreach($clients as $otherClient){
                if($otherClient!=$client){
                    fwrite($otherClient,$data);
                    echo "Gelen mesaj $otherClient e gönderildi\n";
                }
            }
            echo "$client den gelen mesaj: $data\n";
            if (strlen($data) === 0 || $data === "quit" || $data === "exit"){//oyuncu çıkış yaptı
                echo "Bir oyuncu ayrıldı\n";
                fclose($client);
                Loop::removeReadStream($client);
                $clients=array_filter($clients,function($otherClient) use ($client){
                    return $otherClient!=$client;
                });
                unset($client);
                return;
            }
        });
    });